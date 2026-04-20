<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — MarginFlow</title>
    <style>
        :root {
            --line:#e2e8f0;
            --line-soft:#edf2f7;
            --bg:#f3f6fb;
            --text:#0f172a;
            --muted:#64748b;
            --card:#ffffff;
            --head:#f8fafc;
            --primary:#0f172a;
            --primary-soft:#e2e8f0;
            --ok-bg:#dcfce7;
            --ok-text:#14532d;
            --bad-bg:#fee2e2;
            --bad-text:#7f1d1d;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 0% 0%, #ffffff 0, #f3f6fb 45%),
                var(--bg);
        }
        .container { max-width: 1320px; margin: 18px auto 42px; padding: 0 18px; }
        .header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom: 14px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(6px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }
        .brand { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
        .brand-mark {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:12px;
            font-weight:800;
            background: var(--primary);
            color: #fff;
        }
        .brand-name { font-size: 13px; color: #334155; font-weight: 700; letter-spacing: 0.02em; }
        .title { margin:0; font-size: 24px; line-height: 1.25; letter-spacing: -0.01em; }
        .meta { margin:4px 0 0; color: var(--muted); font-size: 13px; line-height: 1.45; }
        .nav {
            display:flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
            overflow: visible;
            padding-bottom: 0;
        }
        .nav a {
            text-decoration:none;
            border:1px solid var(--line);
            background:#fff;
            color:var(--text);
            border-radius:999px;
            padding:8px 13px;
            font-size:13px;
            font-weight:600;
            white-space: nowrap;
            transition: all .15s ease;
        }
        .nav a:hover { border-color:#cbd5e1; background:#f8fafc; }
        .nav a.active { background:var(--primary); color:#fff; border-color:var(--primary); }
        .card {
            background:var(--card);
            border:1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.035);
        }
        .card + .card { margin-top: 14px; }
        .card-head {
            padding:12px 14px;
            background:var(--head);
            border-bottom:1px solid var(--line);
            font-weight:700;
            font-size: 14px;
        }
        .card-body { padding: 14px; }
        .table-wrap { overflow:auto; border:1px solid var(--line); border-radius:10px; }
        table { width:100%; border-collapse: collapse; font-size: 14px; background:#fff; }
        th, td { border-bottom:1px solid var(--line-soft); padding:9px 10px; vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        th {
            background: var(--head);
            text-align:left;
            font-size:12px;
            color:#475569;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .input, .select {
            width:100%;
            border:1px solid #cbd5e1;
            border-radius:9px;
            padding:8px 10px;
            font-size:14px;
            background:#fff;
            color: var(--text);
        }
        .input:focus, .select:focus {
            outline: none;
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.2);
        }
        .btn {
            border:0;
            border-radius:9px;
            padding:9px 12px;
            font-weight:700;
            cursor:pointer;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content: center;
            line-height: 1;
        }
        .btn-primary { background: var(--primary); color:#fff; }
        .btn-primary:hover { background:#1e293b; }
        .btn-ghost { background:#fff; color:var(--text); border:1px solid var(--line); }
        .btn-ghost:hover { background:#f8fafc; border-color:#cbd5e1; }
        .row-actions { display:flex; gap:8px; flex-wrap: wrap; margin-top:10px; }
        .ok {
            background:var(--ok-bg);
            color:var(--ok-text);
            border:1px solid #86efac;
            padding:10px 12px;
            border-radius: 10px;
            margin-bottom:10px;
            font-size:14px;
        }
        .bad {
            background:var(--bad-bg);
            color:var(--bad-text);
            border:1px solid #fca5a5;
            padding:10px 12px;
            border-radius: 10px;
            margin-bottom:10px;
            font-size:14px;
        }
        .num { text-align:right; white-space:nowrap; font-variant-numeric: tabular-nums; }
        @media (max-width: 860px) {
            .container { padding: 0 12px; }
            .header { flex-direction: column; align-items: stretch; }
            .row-actions { margin-top: 0; }
            .nav {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 2px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <div class="brand">
                <div class="brand-mark">MF</div>
                <div class="brand-name">MarginFlow Workbook</div>
            </div>
            <h1 class="title">@yield('title')</h1>
            <p class="meta">@yield('subtitle')</p>
        </div>
        <div class="row-actions">
            <form method="POST" action="{{ route('cabinet.logout') }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Выйти</button>
            </form>
        </div>
    </div>

    <div class="nav">
        @foreach($menu as $item)
            <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">{{ $item['title'] }}</a>
        @endforeach
    </div>

    @if(session('success'))
        <div class="ok">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bad">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</div>
</body>
</html>
