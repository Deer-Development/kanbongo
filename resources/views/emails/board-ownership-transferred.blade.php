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
        .info-section {
            margin: 24px 0;
            border: 1px solid #e1e4e8;
            border-radius: 12px;
            overflow: hidden;
        }
        .info-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e1e4e8;
        }
        .info-content {
            padding: 20px 24px;
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
        }
        .action-button {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #0366d6;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
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
                <h2>
                    @if($action === 'granted')
                        🎉 Board Ownership Granted
                    @else
                        📋 Board Ownership Changed
                    @endif
                </h2>
            </div>
            
            <div class="content">
                <p style="color: #475569; font-size: 16px; text-align: center; margin-bottom: 32px;">
                    Hello {{ $user->name }}, 
                    @if($action === 'granted')
                        you are now the owner of "{{ $container->name }}"
                    @else
                        the ownership of "{{ $container->name }}" has been transferred
                    @endif
                </p>

                <div class="info-section">
                    <div class="info-header">
                        <h3 style="margin: 0; font-size: 16px; color: #0f172a;">
                            @if($action === 'granted')
                                ⭐ New Responsibilities
                            @else
                                📝 Changes to Your Access
                            @endif
                        </h3>
                    </div>
                    <div class="info-content">
                        @if($action === 'granted')
                            <div class="metric">
                                <span class="metric-label">🔑 Access Level</span>
                                <span class="metric-value">Full Administrative Control</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">👥 Management</span>
                                <span class="metric-value">All Board Settings & Permissions</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">🔄 Transfer</span>
                                <span class="metric-value">Can Transfer Ownership</span>
                            </div>
                        @else
                            <div class="metric">
                                <span class="metric-label">👤 New Owner</span>
                                <span class="metric-value">{{ $otherUser->full_name }}</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">🔒 Your Access</span>
                                <span class="metric-value">Member Access Maintained</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-header">
                        <h3 style="margin: 0; font-size: 16px; color: #0f172a;">
                            📋 Transfer Details
                        </h3>
                    </div>
                    <div class="info-content">
                        <div class="metric">
                            <span class="metric-label">📌 Board</span>
                            <span class="metric-value">{{ $container->name }}</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">📂 Project</span>
                            <span class="metric-value">{{ $container->project->name }}</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">📅 Date</span>
                            <span class="metric-value">{{ $date }}</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">
                                @if($action === 'granted')
                                    👤 Previous Owner
                                @else
                                    👤 New Owner
                                @endif
                            </span>
                            <span class="metric-value">{{ $otherUser->full_name }}</span>
                        </div>
                    </div>
                </div>

                <div class="action-button">
                    <a href="{{ url("/projects/{$container->project_id}/boards/{$container->id}") }}" class="button">
                        View Board
                    </a>
                </div>
            </div>

            <div class="footer">
                <p style="margin: 0 0 8px;">
                    If you believe this change was made in error, please contact your project administrator.
                </p>
                <p style="margin: 0; opacity: 0.8;">
                    This is an automated message from {{ config('app.name') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html> 