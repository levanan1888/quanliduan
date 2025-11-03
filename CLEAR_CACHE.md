# Cách sửa lỗi "Route not found"

## Bước 1: Clear Route Cache

Chạy các lệnh sau trong Docker container:

```bash
docker compose exec app php artisan route:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan optimize:clear
```

## Bước 2: Kiểm tra URL đúng

Route `/api/projects` cần được gọi với:
- URL đầy đủ: `http://localhost:8080/api/projects`
- Method: `GET`
- Header: `Authorization: Bearer {token}`

**LƯU Ý:**
- URL phải có `/` ở đầu: `/api/projects` ✅
- Không được thiếu `/` đầu: `api/projects` ❌
- Phải có Bearer token trong header nếu là protected route

## Bước 3: Test Route

```bash
# Test với curl
curl -X GET http://localhost:8080/api/projects \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

## Bước 4: Kiểm tra routes đã được register

```bash
docker compose exec app php artisan route:list --path=projects
```

Bạn sẽ thấy:
```
GET|HEAD  api/projects ................ projects.index › ProjectController@index
POST      api/projects ................ projects.store › ProjectController@store
...
```

