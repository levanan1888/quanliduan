<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Docs</title>
    <style>
        body{font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:20px;background:#0b1020;color:#e6e9ef}
        a{color:#8bd5ca}
        table{border-collapse:collapse;width:100%}
        th,td{border:1px solid #303446;padding:8px;text-align:left}
        th{background:#1f2335}
        code{background:#1f2335;padding:2px 4px;border-radius:3px}
        .pill{border-radius:9999px;padding:2px 8px;margin-right:6px;font-size:12px;border:1px solid #303446;background:#1a2130}
        .GET{color:#8bd5ca;border-color:#8bd5ca}
        .POST{color:#c6a0f6;border-color:#c6a0f6}
        .PUT{color:#f4b8e4;border-color:#f4b8e4}
        .PATCH{color:#ef9f76;border-color:#ef9f76}
        .DELETE{color:#e78284;border-color:#e78284}
        .controls{margin:12px 0;padding:12px;border:1px solid #303446;border-radius:8px;background:#11172a}
        .controls input{width:100%;max-width:520px;margin-right:8px;padding:8px;border-radius:6px;border:1px solid #303446;background:#0e1527;color:#e6e9ef}
        .muted{color:#a6adc8;font-size:12px}
    </style>
    <script>
        function base(){
            const el = document.getElementById('baseInput');
            return (el && el.value) ? el.value : location.origin;
        }
        function token(){
            const el = document.getElementById('tokenInput');
            return el ? el.value.trim() : '';
        }
        function copyCurl(method, uri){
            const url = `${base()}${uri.startsWith('/')?uri:'/'+uri}`;
            const parts = [`curl -X ${method} \"${url}\"`, `-H \"Accept: application/json\"`];
            const t = token();
            if(t){ parts.push(`-H \"Authorization: Bearer ${t}\"`); }
            if(method !== 'GET' && method !== 'DELETE'){
                parts.push(`-H \"Content-Type: application/json\"`);
                parts.push(`-d '{}'`);
            }
            const curl = parts.join(' ');
            navigator.clipboard.writeText(curl);
            alert('Copied: '+curl);
        }
    </script>
    </head>
<body>
    <h1>Danh sách API</h1>
    <div class="controls">
        <div style="margin-bottom:8px">
            <label>Base URL</label><br>
            <input id="baseInput" placeholder="http://localhost:8080" value="{{ request()->getSchemeAndHttpHost() }}">
        </div>
        <div>
            <label>Token (Bearer)</label><br>
            <input id="tokenInput" placeholder="1|your_token_here">
            <div class="muted">Khi nhập token, nút Copy cURL sẽ tự thêm header Authorization.</div>
        </div>
    </div>
    <h2>Endpoints</h2>
    <table>
        <thead>
            <tr>
                <th>Phương thức</th>
                <th>Đường dẫn</th>
                <th>Tên route</th>
                <th>Action</th>
                <th>Mô tả</th>
                <th>Middleware</th>
                <th>Curl</th>
            </tr>
        </thead>
        <tbody>
        @foreach($routes as $r)
            <tr>
                <td>@foreach($r['methods'] as $m)<span class="pill {{ $m }}">{{ $m }}</span>@endforeach</td>
                <td><code>{{ $r['uri'] }}</code></td>
                <td>{{ $r['name'] ?: '-' }}</td>
                <td><code>{{ $r['action'] }}</code></td>
                <td>
                    {{ $descriptions[strtoupper($r['methods'][0]).' '.$r['uri']] ?? '-' }}
                </td>
                <td>
                    @foreach($r['middleware'] as $mw)
                        <code style="display:inline-block;margin-right:4px">{{ $mw }}</code>
                    @endforeach
                </td>
                <td><button onclick="copyCurlFull('{{ $r['methods'][0] }}','{{ $r['uri'] }}')">Copy cURL</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2 style="margin-top:24px">Ví dụ cURL sẵn sàng chạy</h2>
    <div class="controls">
        @foreach($examples as $ex)
            <div style="margin-bottom:12px">
                <div><strong>{{ $ex['title'] }}</strong></div>
                <code id="ex{{ $loop->index }}"></code>
                <div>
                    <button onclick="buildEx({{ json_encode($ex) }}, 'ex{{ $loop->index }}')">Tạo cURL</button>
                    <button onclick="copyEx('ex{{ $loop->index }}')">Copy</button>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function buildEx(ex, id){
            const url = `${base()}${ex.uri}`;
            const parts = [`curl -X ${ex.method} \"${url}\"`, `-H \"Accept: application/json\"`];
            if(ex.auth){
                const t = token();
                if(t){ parts.push(`-H \"Authorization: Bearer ${t}\"`); }
            }
            if(ex.method !== 'GET' && ex.method !== 'DELETE'){
                parts.push(`-H \"Content-Type: application/json\"`);
                parts.push(`-d '${JSON.stringify(ex.body)}'`);
            }
            document.getElementById(id).innerText = parts.join(' ');
        }
        function copyEx(id){
            const text = document.getElementById(id).innerText;
            navigator.clipboard.writeText(text);
            alert('Copied');
        }

        const exampleMap = @json($exampleMap ?? []);
        function copyCurlFull(method, uri){
            const key = method + ' ' + (uri.startsWith('/')?uri:'/'+uri);
            const ex = exampleMap[key];
            const url = `${base()}${uri.startsWith('/')?uri:'/'+uri}`;
            const parts = [`curl -i -X ${method} \"${url}\"`, `-H \"Accept: application/json\"`];
            const needsAuth = ex ? !!ex.auth : uri.startsWith('/api/');
            const t = token();
            if(needsAuth && t){ parts.push(`-H \"Authorization: Bearer ${t}\"`); }
            const body = ex ? ex.body : {};
            if(method !== 'GET' && method !== 'DELETE'){
                parts.push(`-H \"Content-Type: application/json\"`);
                parts.push(`--data '${JSON.stringify(body)}'`);
            }
            const curl = parts.join(' ');
            navigator.clipboard.writeText(curl);
            alert('Copied: '+curl);
        }
    </script>
</body>
</html>


