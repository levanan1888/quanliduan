# Cách sửa lỗi "Failed to save: Insufficient permissions"

## Nguyên nhân
File trong WSL có thể thuộc về root (từ Docker container), nên Windows/Cursor không thể ghi.

## Giải pháp

### Cách 1: Sửa quyền từ Docker Container (Khuyến nghị)

```bash
docker compose exec app bash -c "chown -R 1000:1000 /var/www/html/routes && chmod -R 775 /var/www/html/routes"
```

Số 1000 thường là UID của user WSL.

### Cách 2: Sửa quyền từ WSL Terminal

Mở WSL terminal và chạy:

```bash
cd /home/levanan3418/quanlyduan
sudo chown -R $USER:$USER routes/
sudo chmod -R 775 routes/
```

### Cách 3: Restart Docker và fix quyền

```bash
# Stop containers
docker compose down

# Fix permissions trong WSL
cd /home/levanan3418/quanlyduan
sudo chown -R $(whoami):$(whoami) routes/

# Start lại
docker compose up -d
```

### Cách 4: Copy file và paste lại

1. Copy toàn bộ nội dung file `routes/api.php`
2. Xóa file cũ
3. Tạo file mới và paste lại
4. Save

---

Sau khi sửa quyền, thử Save lại file `routes/api.php`.

