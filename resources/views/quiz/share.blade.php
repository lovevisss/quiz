<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $share['title'] }}</title>
        <meta name="description" content="{{ $share['description'] }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $share['title'] }}">
        <meta property="og:description" content="{{ $share['description'] }}">
        <meta property="og:image" content="{{ $share['image'] }}">
        <meta property="og:url" content="{{ $share['link'] }}">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <style>
            :root {
                color-scheme: light;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family: "PingFang SC", "Microsoft YaHei", sans-serif;
                background:
                    radial-gradient(circle at top right, rgba(14, 165, 233, 0.18), transparent 32%),
                    linear-gradient(180deg, #f8fafc, #eef6ff 45%, #f8fafc 100%);
                color: #0f172a;
            }

            .shell {
                width: min(100%, 760px);
                margin: 0 auto;
                padding: 24px 16px 48px;
            }

            .hero {
                overflow: hidden;
                border: 1px solid rgba(16, 185, 129, 0.18);
                border-radius: 28px;
                background: linear-gradient(135deg, rgba(236, 253, 245, 0.96), rgba(255, 255, 255, 0.98), rgba(224, 242, 254, 0.98));
                box-shadow: 0 24px 48px -32px rgba(15, 23, 42, 0.28);
                padding: 24px;
            }

            .eyebrow {
                margin: 0;
                font-size: 12px;
                letter-spacing: 0.24em;
                text-transform: uppercase;
                color: #059669;
            }

            h1 {
                margin: 12px 0 0;
                font-size: clamp(28px, 4vw, 38px);
                line-height: 1.15;
            }

            .desc {
                margin: 14px 0 0;
                font-size: 15px;
                line-height: 1.8;
                color: #475569;
            }

            .score-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                margin-top: 20px;
            }

            .stat {
                border-radius: 22px;
                border: 1px solid rgba(148, 163, 184, 0.2);
                background: rgba(255, 255, 255, 0.84);
                padding: 16px;
            }

            .stat-label {
                font-size: 12px;
                color: #64748b;
            }

            .stat-value {
                margin-top: 8px;
                font-size: 28px;
                font-weight: 700;
                color: #0f172a;
            }

            .chips {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 20px;
            }

            .chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 10px 14px;
                background: rgba(255, 255, 255, 0.88);
                color: #334155;
                font-size: 13px;
                box-shadow: 0 8px 20px -18px rgba(15, 23, 42, 0.5);
            }

            .actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
                margin-top: 24px;
            }

            .button {
                display: inline-flex;
                justify-content: center;
                align-items: center;
                min-height: 48px;
                border-radius: 16px;
                text-decoration: none;
                font-weight: 600;
                transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            }

            .button-primary {
                background: #0284c7;
                color: #fff;
                box-shadow: 0 16px 30px -20px rgba(2, 132, 199, 0.78);
            }

            .button-secondary {
                border: 1px solid rgba(148, 163, 184, 0.45);
                background: rgba(255, 255, 255, 0.92);
                color: #334155;
            }

            .button:hover {
                transform: translateY(-1px);
            }

            @media (min-width: 640px) {
                .shell {
                    padding-top: 40px;
                }

                .actions {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
        </style>
    </head>
    <body>
        <main class="shell">
            <section class="hero">
                <p class="eyebrow">微信朋友圈分享</p>
                <h1>{{ $share['activity_name'] }}</h1>
                <p class="desc">{{ $share['description'] }}</p>

                <div class="score-grid">
                    <div class="stat">
                        <div class="stat-label">本次得分</div>
                        <div class="stat-value">{{ $share['score'] }}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">答对题数</div>
                        <div class="stat-value">{{ $share['correct_count'] }}/{{ $share['total_questions'] }}</div>
                    </div>
                </div>

                <div class="chips">
                    <span class="chip">错题 {{ $share['wrong_count'] }} 题</span>
                    @if (! empty($share['submitted_at']))
                        <span class="chip">提交于 {{ \Illuminate\Support\Carbon::parse($share['submitted_at'])->format('Y-m-d H:i') }}</span>
                    @endif
                </div>

                <div class="actions">
                    <a href="{{ route('quiz.index') }}" class="button button-primary">我也要参加答题</a>
                    <a href="{{ $share['link'] }}" class="button button-secondary">复制/打开分享链接</a>
                </div>
            </section>
        </main>
    </body>
</html>

