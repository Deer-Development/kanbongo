<?php

namespace App\Services\Container;

use App\Http\Resources\Container\ContainerResource;
use App\Models\Container;
use App\Models\Paycheck;
use App\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Notifications\BoardOwnershipTransferred;

class ContainerService extends BaseService
{
    protected string $modelClass = Container::class;

    // Define valid columns for sorting
    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    public function create(array $data): Container
    {
        return DB::transaction(function () use ($data) {
            $containerData = $this->prepareContainerData($data);
            $container = $this->getModelInstance()->create($containerData);

            $container->members()->create([
                'user_id' => $container->owner_id,
                'billable_rate' => null,
            ]);

            return $container;
        }, 3);
    }

    public function update($id, $data): Container
    {
        return DB::transaction(function () use ($id, $data) {
            $containerData = $this->prepareContainerData($data);

            $members = $data['members'] ?? [];

            $container = $this->getModelInstance()->findOrFail($id);
            $container->update($containerData);

            if (count($members)) {
                $container->members()->get()->map(function ($member) use ($members) {
                    if (!in_array($member->user_id, array_column($members, 'user_id'))) {
                        $member->delete();
                    }
                });

                foreach ($members as $memberData) {
                    $container->members()->updateOrCreate(
                        [
                            'user_id' => $memberData['user_id'],
                        ],
                        $memberData
                    );
                }
            }

            return $container;
        }, 3);
    }

    public function updateState(int $id, array $data): ContainerResource
    {
        $container = $this->getById($id)->load('boards');

        foreach ($data as $index => $boardId) {
            $container
                ->boards()->where('id', $boardId)
                ->update(['order' => $index + 1]);
        }

        return new ContainerResource($container);
    }


    private function prepareContainerData(array $data): array
    {
        return [
            'name' => $data['name'],
            'project_id' => $data['project_id'],
            'is_active' => $data['is_active'],
            'owner_id' => $data['owner_id'] ?? Auth::user()->id,
        ];
    }

    public function processPayment(int $id, int $userId, $dateRange, $selectedEntries = []): Container
    {
        if (count($selectedEntries)) {
            $model = $this->getModelInstance()::with([
                'boards' => function ($q) use ($selectedEntries) {
                    $q->orderBy('created_at')->with([
                        'tasks' => function ($q) use ($selectedEntries) {
                            $q->with([
                                'timeEntries' => function ($q) use ($selectedEntries) {
                                    $q->whereIn('id', $selectedEntries);
                                },
                                'members'
                            ]);
                        }
                    ]);
                },
                'members' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ])->findOrFail($id);
        } else {
            $startDate = null;
            $endDate = null;
            if ($dateRange) {
                [$start, $end] = array_pad(explode(' to ', $dateRange), 2, null);
                $startDate = Carbon::parse($start)->startOfDay();
                $endDate = $end ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
            }

            $model = $this->getModelInstance()::with([
                'boards' => function ($q) use ($startDate, $endDate) {
                    $q->orderBy('created_at')->with([
                        'tasks' => function ($q) use ($startDate, $endDate) {
                            $q->with([
                                'timeEntries' => function ($q) use ($startDate, $endDate) {
                                    if ($startDate) {
                                        $q->where('start', '>=', $startDate);
                                    }
                                    if ($endDate) {
                                        $q->where('end', '<=', $endDate);
                                    }
                                    $q->where('is_paid', false);
                                },
                                'members'
                            ]);
                        }
                    ]);
                },
                'members' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ])->findOrFail($id);
        }

        DB::transaction(function () use ($model, $userId) {
            foreach ($model->members as $member) {
                $totalHours = 0;
                $totalAmount = 0;
                $timeEntryIds = [];

                foreach ($model->boards as $board) {
                    foreach ($board->tasks as $task) {
                        foreach ($task->timeEntries as $timeEntry) {
                            if ($timeEntry->user_id === $member->user_id && !$timeEntry->is_paid) {
                                $durationInHours = Carbon::parse($timeEntry->start)
                                        ->diffInMinutes(Carbon::parse($timeEntry->end)) / 60;

                                $amountPaid = $durationInHours * $member->billable_rate;

                                $timeEntry->update([
                                    'is_paid' => true,
                                    'paid_rate' => $member->billable_rate,
                                    'amount_paid' => $amountPaid
                                ]);

                                $totalHours += $durationInHours;
                                $totalAmount += $amountPaid;
                                $timeEntryIds[] = $timeEntry->id;
                            }
                        }
                    }
                }

                if ($totalHours > 0 && $totalAmount > 0) {
                    $paycheck = Paycheck::create([
                        'user_id' => $member->user_id,
                        'container_id' => $model->id,
                        'project_id' => $model->project_id,
                        'created_by' => Auth::user()->id,
                        'total_hours' => $totalHours,
                        'total_amount' => $totalAmount,
                        'status' => 'paid',
                    ]);

                    DB::table('time_entries')
                        ->whereIn('id', $timeEntryIds)
                        ->update(['paycheck_id' => $paycheck->id]);
                }
            }
        });

        return $model;
    }

    public function transferOwnership(Container $container, int $newOwnerId): void
    {
        DB::transaction(function () use ($container, $newOwnerId) {
            $oldOwnerId = $container->owner_id;
            
            // Update container owner
            $container->update(['owner_id' => $newOwnerId]);
            
            // Notify relevant users
            $oldOwner = User::find($oldOwnerId); // Important: Get old owner ID before update
            $newOwner = User::find($newOwnerId);
            
            // Notify old owner that ownership was removed
            $oldOwner->notify(new BoardOwnershipTransferred($container, $newOwner, 'removed'));
            
            // Notify new owner that ownership was granted
            $newOwner->notify(new BoardOwnershipTransferred($container, $oldOwner, 'granted'));
        });
    }
}
