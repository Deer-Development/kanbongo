<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #24292e;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            background-color: #f6f8fa;
        }
        .wrapper {
            width: 100%;
            padding: 40px 0;
            background: linear-gradient(180deg, #f6f8fa 0%, #ffffff 100%);
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(27,31,35,0.04);
        }
        .header {
            padding: 24px;
            border-bottom: 1px solid #e1e4e8;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 32px 24px;
        }
        .intro {
            color: #475569;
            font-size: 15px;
            margin-bottom: 24px;
            padding: 0 4px;
        }
        .section {
            margin: 24px 0;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .section-header {
            padding: 16px 20px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        .section-header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .count-badge {
            background: #e2e8f0;
            color: #475569;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .activity-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.15s ease;
        }
        .activity-item:hover {
            background-color: #f8fafc;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-content {
            color: #334155;
            font-size: 14px;
            line-height: 1.6;
        }
        .activity-meta {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .meta-dot {
            height: 3px;
            width: 3px;
            background-color: #cbd5e1;
            border-radius: 50%;
        }
        .footer {
            padding: 24px;
            border-top: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            text-align: center;
            color: #64748b;
            font-size: 13px;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .view-all {
            display: block;
            text-align: center;
            margin-top: 24px;
            padding: 12px 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #0f172a;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.15s ease;
        }
        .view-all:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        @media (max-width: 600px) {
            .wrapper { padding: 0; }
            .container { border-radius: 0; }
            .content { padding: 24px 16px; }
            .activity-item { padding: 14px 16px; }
        }
        .welcome-banner {
            background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%);
            color: white;
            padding: 32px 24px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 32px;
        }
        .welcome-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 8px;
        }
        .welcome-subtitle {
            font-size: 16px;
            margin: 0;
        }
        .activity-type-icon {
            font-size: 16px;
            margin-right: 8px;
        }
        .notification-preview {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin-top: 8px;
            border-radius: 0 4px 4px 0;
            font-style: italic;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h2>Recent Updates & Activities 🔔</h2>
            </div>
            
            <div class="content">
                <div class="welcome-banner">
                    <h1 class="welcome-title">Hey {{ $user->first_name }}! 👋</h1>
                    <p class="welcome-subtitle">Here's what happened in the last 4 hours</p>
                </div>

                @if(!empty($digestData['activities']))
                    <div class="section">
                        <div class="section-header">
                            <h3>
                                Recent Activities
                                <span class="count-badge">{{ count($digestData['activities']) }}</span>
                            </h3>
                        </div>
                        @foreach($digestData['activities'] as $activity)
                            <div class="activity-item">
                                <div class="activity-content">
                                    <span class="activity-type-icon">
                                        @switch($activity['event'])
                                            @case('created')
                                                🆕
                                                @break
                                            @case('updated')
                                                ✏️
                                                @break
                                            @case('deleted')
                                                🗑️
                                                @break
                                            @case('completed')
                                                ✅
                                                @break
                                            @case('member_added')
                                                👥
                                                @break
                                            @case('member_removed')
                                                👤
                                                @break
                                            @case('time_entry_completed')
                                                ⏱️
                                                @break
                                            @default
                                                📝
                                        @endswitch
                                    </span>
                                    {{ $activity['description'] }}
                                </div>
                                <div class="activity-meta">
                                    {{ $activity['created_at'] }}
                                    <span class="meta-dot"></span>
                                    {{ $activity['user']['name'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($digestData['notifications']))
                    <div class="section">
                        <div class="section-header">
                            <h3>
                                Unread Notifications
                                <span class="count-badge">{{ count($digestData['notifications']) }}</span>
                            </h3>
                        </div>
                        @foreach($digestData['notifications'] as $notification)
                            <div class="activity-item">
                                @php
                                    $data = $notification['data'];
                                    $message = '';
                                    $icon = '📢';
                                    
                                    if (isset($data['author']) && isset($data['task_name'])) {
                                        if (isset($data['comment_preview'])) {
                                            $icon = '💬';
                                            $message = "{$data['author']['name']} commented on task #{$data['task_sequence_id']} {$data['task_name']}";
                                        } else {
                                            $icon = '🔔';
                                            $message = "{$data['author']['name']} {$data['action']['text']} task #{$data['task_sequence_id']} {$data['task_name']}";
                                        }
                                        
                                        if (isset($data['container_name']) && $data['container_name'] !== 'Unknown Board') {
                                            $message .= " in {$data['container_name']}";
                                        }
                                    } elseif (isset($data['message'])) {
                                        $message = $data['message'];
                                    }
                                @endphp
                                
                                <div class="activity-content">
                                    <span class="activity-type-icon">{{ $icon }}</span>
                                    {{ $message }}
                                </div>
                                @if(isset($data['comment_preview']))
                                    <div class="notification-preview">
                                        "{{ $data['comment_preview'] }}"
                                    </div>
                                @endif
                                <div class="activity-meta">{{ $notification['created_at'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <a href="{{ config('app.url') }}/dashboard" class="view-all">
                    📱 Open Dashboard
                </a>
            </div>

            <div class="footer">
                <p>🌟 Stay up to date with your team!</p>
                <p>This is an automated message from {{ config('app.name') }}</p>
            </div>
        </div>
    </div>
</body>
</html> 