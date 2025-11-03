<!DOCTYPE html>
<html>
<head>
    <title>API Documentation - Project Management System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .swagger-ui {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .postman-download {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .postman-download:hover {
            background: #45a049;
        }
    </style>
    </head>
<body>
    <a href="{{ url('/postman-collection/download') }}" class="postman-download" target="_blank">
        📥 Download Postman Collection
    </a>
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />

    <script>
        window.onload = function() {
            // Try to get JSON from generated swagger docs first, fallback to our endpoint
            const jsonUrl = "{{ url('/api-docs.json') }}";
            
            const ui = SwaggerUIBundle({
                url: jsonUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                // Enable persistence for auth token
                persistAuthorization: true,
                requestInterceptor: (request) => {
                    // Swagger UI tự động inject token từ form Authorize
                    // Nhưng để đảm bảo, ta sẽ lấy từ localStorage nếu Swagger UI chưa có
                    const currentAuth = request.headers['Authorization'] || request.headers['authorization'];
                    
                    if (!currentAuth) {
                        const token = localStorage.getItem('api_token');
                        if (token) {
                            // Đảm bảo token không có "Bearer " prefix
                            const cleanToken = token.startsWith('Bearer ') ? token.substring(7) : token;
                            request.headers['Authorization'] = 'Bearer ' + cleanToken;
                        }
                    } else if (currentAuth && !currentAuth.startsWith('Bearer ')) {
                        // Đảm bảo header có format "Bearer {token}"
                        request.headers['Authorization'] = 'Bearer ' + currentAuth.replace(/^Bearer\s+/i, '');
                    }
                    
                    // Debug: log token để kiểm tra
                    if (request.headers['Authorization']) {
                        console.log('Token injected:', request.headers['Authorization'].substring(0, 20) + '...');
                    }
                    
                    return request;
                },
                responseInterceptor: (response) => {
                    // Auto-save token from login/register response
                    if (response.url && (response.url.includes('/auth/login') || response.url.includes('/auth/register')) && response.status === 200) {
                        try {
                            const data = JSON.parse(response.text);
                            if (data.access_token) {
                                // Lưu token vào localStorage (chỉ lưu token, không có "Bearer ")
                                const token = data.access_token.startsWith('Bearer ') 
                                    ? data.access_token.substring(7) 
                                    : data.access_token;
                                localStorage.setItem('api_token', token);
                                
                                // Tự động authorize trong Swagger UI
                                setTimeout(() => {
                                    if (window.ui && window.ui.preauthorizeApiKey) {
                                        window.ui.preauthorizeApiKey('sanctum', token);
                                    } else {
                                        // Fallback: click authorize button manually
                                        const authBtn = document.querySelector('.btn.authorize');
                                        if (authBtn && !authBtn.classList.contains('authorized')) {
                                            authBtn.click();
                                            setTimeout(() => {
                                                const inputs = document.querySelectorAll('input[type="password"], input[placeholder*="token"], input[placeholder*="Bearer"]');
                                                inputs.forEach(input => {
                                                    if (input.value === '' || !input.value) {
                                                        input.value = token;
                                                        input.dispatchEvent(new Event('input', { bubbles: true }));
                                                        input.dispatchEvent(new Event('change', { bubbles: true }));
                                                    }
                                                });
                                                
                                                const authorizeBtn = document.querySelector('.btn-done, .authorize-btn');
                                                if (authorizeBtn) {
                                                    setTimeout(() => authorizeBtn.click(), 200);
                                                }
                                            }, 300);
                                        }
                                    }
                                }, 1000);
                            }
                        } catch (e) {
                            console.error('Could not parse auth response:', e);
                        }
                    }
                    return response;
                },
                onComplete: () => {
                    // Add helper text
                    setTimeout(() => {
                        const infoDiv = document.createElement('div');
                        infoDiv.style.cssText = 'padding: 15px; margin: 10px; background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 4px; font-size: 14px;';
                        infoDiv.innerHTML = `
                            <strong>💡 Hướng dẫn sử dụng Token:</strong><br>
                            1. Login qua <code>POST /api/auth/login</code> → Token sẽ tự động được lưu<br>
                            2. Hoặc click nút <strong>"Authorize"</strong> (🔓) ở góc trên phải<br>
                            3. <strong>Chú ý:</strong> Khi nhập token, chỉ nhập token (không có "Bearer "), ví dụ: <code>1|abc123xyz...</code><br>
                            4. Sau khi authorize, tất cả requests sẽ tự động có token trong header
                        `;
                        const swaggerContainer = document.getElementById('swagger-ui');
                        if (swaggerContainer) {
                            swaggerContainer.insertBefore(infoDiv, swaggerContainer.firstChild);
                        }
                    }, 1000);
                }
            });

            window.ui = ui;
        };
    </script>
</body>
</html>
