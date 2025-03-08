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
        .board-section h3 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .metric-group {
            margin: 12px 0;
            padding: 12px 0;
            border-top: 1px solid #eaecef;
        }
        .metric {
            display: flex;
            align-items: center;
            margin: 8px 0;
            font-size: 14px;
            color: #475569;
            padding: 4px 0;
        }
        .metric-label {
            width: 120px;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .metric-value {
            color: #0f172a;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .metric-value.positive {
            color: #059669;
        }
        .summary-section {
            margin-top: 32px;
            padding: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .summary-section h3 {
            margin: 0 0 16px;
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
                <h2>✨ Daily Activity Summary</h2>
            </div>
            
            <div class="content">
                <p style="color: #475569; font-size: 16px; text-align: center; margin-bottom: 32px;">
                    Here's your activity summary for {{ Carbon\Carbon::now()->format('F j, Y') }} 📅
                </p>

                @if(empty($activityData['containers']))
                    <div class="board-section" style="padding: 24px; text-align: center;">
                        <p style="color: #64748b; margin: 0; font-size: 15px;">
                            🌤️ No time tracked today. Ready to start fresh?
                        </p>
                    </div>
                @else
                    @foreach($activityData['containers'] as $container)
                        <div class="board-section">
                            <div style="padding: 20px 24px; background: #f8fafc; border-bottom: 1px solid #e1e4e8;">
                                <h3>📋 {{ $container['project_name'] }}</h3>
                            </div>
                            <div style="padding: 20px 24px;">
                                <div class="metric">
                                    <span class="metric-label">
                                        <span>⏱️</span>
                                        <span>Time tracked</span>
                                    </span>
                                    <span class="metric-value">{{ $container['total_hours'] }} hours</span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">
                                        <span>💰</span>
                                        <span>Earnings</span>
                                    </span>
                                    <span class="metric-value positive">${{ number_format($container['total_amount'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="summary-section">
                        <h3>
                            <span>📊</span>
                            <span>Daily Summary</span>
                        </h3>
                        <div style="background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div class="metric">
                                <span class="metric-label">
                                    <span>⭐</span>
                                    <span>Total time</span>
                                </span>
                                <span class="metric-value">{{ $activityData['total_hours'] }} hours</span>
                            </div>
                            <div class="metric" style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e2e8f0;">
                                <span class="metric-label">
                                    <span>🎯</span>
                                    <span>Total earnings</span>
                                </span>
                                <span class="metric-value positive">${{ number_format($activityData['total_income'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="footer">
                <p style="margin: 0 0 8px;">
                    🌟 Keep up the great work!
                </p>
                <p style="margin: 0; opacity: 0.8;">
                    This is an automated message from {{ config('app.name') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html> 