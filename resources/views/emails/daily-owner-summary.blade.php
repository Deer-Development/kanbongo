<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #24292e;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            background: #f6f8fa;
        }
        .wrapper {
            width: 100%;
            background: linear-gradient(180deg, #f6f8fa 0%, #ffffff 100%);
            padding: 32px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e1e4e8;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(27,31,35,0.04);
        }
        .header {
            padding: 24px;
            border-bottom: 1px solid #e1e4e8;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
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
        .board-section {
            margin: 24px 0;
            border: 1px solid #e1e4e8;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .board-section:hover {
            box-shadow: 0 4px 12px rgba(27,31,35,0.08);
            border-color: #0366d6;
        }
        .board-header {
            padding: 20px 24px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e1e4e8;
        }
        .board-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .member-section {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.15s ease;
        }
        .member-section:hover {
            background-color: #f8fafc;
        }
        .member-section:last-child {
            border-bottom: none;
        }
        .member-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 15px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .metric {
            display: flex;
            align-items: center;
            margin: 6px 0;
            font-size: 14px;
            color: #475569;
        }
        .metric-label {
            width: 100px;
            color: #64748b;
            font-weight: 500;
        }
        .metric-value {
            color: #0f172a;
            font-weight: 600;
        }
        .metric-value.earnings {
            color: #059669;
        }
        .board-summary {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .daily-overview {
            margin-top: 32px;
            padding: 24px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .daily-overview h3 {
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .footer {
            padding: 24px;
            border-top: 1px solid #e1e4e8;
            color: #64748b;
            font-size: 13px;
            text-align: center;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h2>📊 Team Activity Report</h2>
            </div>
            
            <div class="content">
                <p style="color: #475569; font-size: 16px; text-align: center; margin-bottom: 32px;">
                    Here's your team's activity summary for {{ Carbon\Carbon::now()->format('F j, Y') }} 📅
                </p>

                @if(empty($activityData['containers']))
                    <div class="board-section" style="padding: 24px; text-align: center;">
                        <p style="color: #64748b; margin: 0; font-size: 15px;">
                            🌤️ No activity tracked in your boards today.
                        </p>
                    </div>
                @else
                    @foreach($activityData['containers'] as $container)
                        <div class="board-section">
                            <div class="board-header">
                                <h3>🎯 {{ $container['project_name'] }} / {{ $container['name'] }}</h3>
                            </div>
                            
                            @foreach($container['members'] as $member)
                                <div class="member-section">
                                    <div class="member-name">
                                        👤 {{ $member['name'] }}
                                    </div>
                                    <div class="metric">
                                        <span class="metric-label">Time</span>
                                        <span class="metric-value">⏱️ {{ $member['total_time'] }}</span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-label">Cost</span>
                                        <span class="metric-value earnings">💰 ${{ number_format($member['total_amount'], 2) }}</span>
                                    </div>
                                </div>
                            @endforeach

                            <div class="board-summary">
                                <div class="metric">
                                    <span class="metric-label">Total time</span>
                                    <span class="metric-value">⭐ {{ $container['total_hours_this_period'] }} hours</span>
                                </div>
                                <div class="metric" style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                                    <span class="metric-label">Total cost</span>
                                    <span class="metric-value earnings">💎 ${{ number_format($container['total_amount'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="daily-overview">
                        <h3>
                            <span>📈</span>
                            <span>Daily Overview</span>
                        </h3>
                        <div class="metric">
                            <span class="metric-label">Spending</span>
                            <span class="metric-value earnings">💰 ${{ number_format($activityData['total_spending'], 2) }}</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Pending</span>
                            <span class="metric-value">⏳ ${{ number_format($activityData['pending_spending'], 2) }}</span>
                        </div>
                        <div class="metric" style="margin-top: 16px; padding-top: 16px; border-top: 1px dashed #e2e8f0;">
                            <span class="metric-label">Total</span>
                            <span class="metric-value earnings">🏆 ${{ number_format($activityData['grand_total'], 2) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="footer">
                <p style="margin: 0 0 8px;">
                    🌟 Another productive day with your team!
                </p>
                <p style="margin: 0; opacity: 0.8;">
                    This is an automated message from {{ config('app.name') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html> 