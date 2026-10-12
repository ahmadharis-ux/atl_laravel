# Setup Supervisor untuk Laravel Queue Worker (Server Development)

Agar queue worker jalan otomatis di server development tanpa perlu `php artisan queue:work` manual.

---

## 1. Install Supervisor

```bash
# Ubuntu/Debian
sudo apt update && sudo apt install -y supervisor

# CentOS/RHEL/AlmaLinux
sudo yum install -y supervisor
# atau
sudo dnf install -y supervisor

# Alpine
sudo apk add supervisor
```

## 2. Buat Config Laravel Worker

```bash
sudo tee /etc/supervisor/conf.d/laravel-worker.conf > /dev/null <<'EOF'
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/atl_laravel/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/atl_laravel/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
environment=APP_ENV="development",APP_DEBUG="true"
EOF
```

**Penjelasan:**
- `numprocs=2` → 2 worker paralel (sesuaikan CPU core)
- `--sleep=3` → jeda 3 detik kalau queue kosong
- `--tries=3` → retry 3x kalau job gagal
- `--max-time=3600` → restart worker tiap 1 jam (prevent memory leak)
- `user=www-data` → user yang jalan (sesuaikan server)
- `redirect_stderr=true` → error log masuk ke stdout_logfile

---

## 3. Reload Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Output seharusnya:
```
laravel-worker:laravel-worker_00   RUNNING   pid 1234, uptime 0:00:00
laravel-worker:laravel-worker_01   RUNNING   pid 1235, uptime 0:00:00
```

---

## 4. Command Berguna

```bash
# Lihat status
sudo supervisorctl status laravel-worker:*

# Restart worker (misal setelah deploy)
sudo supervisorctl restart laravel-worker:*

# Stop
sudo supervisorctl stop laravel-worker:*

# Lihat log real-time
sudo tail -f /var/www/atl_laravel/storage/logs/worker.log

# Restart specific process
sudo supervisorctl restart laravel-worker:laravel-worker_00
```

---

## 5. Auto-start saat Boot (Sudah default)

Supervisor biasanya sudah enable di boot. Cek:
```bash
sudo systemctl is-enabled supervisor
# atau
sudo systemctl enable supervisor
```

---

## 6. Deploy Script (Opsional)

Tambahkan ke script deploy CI/CD (`main-cd.yml`):

```yaml
- name: Restart Queue Workers
  run: |
    ssh -i ~/.ssh/id_rsa -o ProxyCommand="cloudflared access ssh --hostname $SSH_HOST" -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST << 'EOF'
      cd /var/www/atl_laravel
      sudo supervisorctl restart laravel-worker:*
      echo "Queue workers restarted"
    EOF
```

---

## 7. Monitoring (Opsional)

### Laravel Horizon (Jika pakai Redis)
Lebih bagus untuk monitoring tapi butuh Redis. Install:
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```
Config: `config/horizon.php` → atur `balanceMaxShift`, `maxProcesses`, dll.

### Sederhana: Logrotate untuk Worker Log
```bash
sudo tee /etc/logrotate.d/laravel-worker > /dev/null <<'EOF'
/var/www/atl_laravel/storage/logs/worker.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        supervisorctl restart laravel-worker:* > /dev/null 2>&1 || true
    endscript
}
EOF
```

---

## 8. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Worker tidak start | `sudo supervisorctl tail -f laravel-worker:laravel-worker_00` |
| Memory leak | Pastikan `--max-time=3600` di config |
| Job stuck | `sudo supervisorctl restart laravel-worker:*` |
| Permission denied | Pastikan `user=www-data` & folder writable |
| Job timeout | Tambah `--timeout=60` di command |

---

## 9. Local Development (Tidak Perlu Supervisor)

Di local, pakai manual:
```bash
# Terminal 1
php artisan queue:work

# Atau dengan Laravel Pail (UI log)
composer require laravel/pail --dev
php artisan pail
```

---

## Catatan Penting

1. **Pastikan `.env` server** sudah benar: `QUEUE_CONNECTION=database`
2. **Tabel `jobs`** sudah dibuat (migration default Laravel)
3. **Storage writable**: `chmod -R 775 storage bootstrap/cache`
4. **Deploy**: Selalu restart worker setelah deploy kalau ada perubahan job/code
5. **Log**: Cek `/var/www/atl_laravel/storage/logs/worker.log` kalau error