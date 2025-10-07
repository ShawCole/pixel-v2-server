# Pixel v2 Server — Future Update Workflow

This document captures the minimal, repeatable steps to update and deploy the Pixel v2 server code.

Context
- VM host: superpixel-php (IP: 35.196.36.229)
- Server code on VM: /var/www/pixel-v2-server (owned by www-data)
- Deploy Key: configured for www-data and added to GitHub (read-only)
- API domain: v2pixel-api.thynkdata.com (HTTP works now; add TLS after DNS points to the VM)
- Worker services: php-worker@NormalizeWorker (others optional)

Quick path (the 90% case)
1) On your Mac
   ```bash
   cd /Users/ShawCole/Documents/Pixel_v2/pixel-v2-server
   git add -A && git commit -m "<message>"
   git push origin main
   ```

2) On the VM (as your login user)
   ```bash
   # Pull latest using www-data deploy key
   sudo -u www-data git -C /var/www/pixel-v2-server fetch --all --prune
   sudo -u www-data git -C /var/www/pixel-v2-server checkout main
   sudo -u www-data git -C /var/www/pixel-v2-server reset --hard origin/main

   # Keep logs directory writable
   sudo install -d -o www-data -g www-data -m 775 /var/www/pixel-v2-server/storage/logs

   # Restart worker(s) and verify
   sudo systemctl restart php-worker@NormalizeWorker
   sudo journalctl -u php-worker@NormalizeWorker -n 50 --no-pager || true
   sudo tail -n 20 /var/www/pixel-v2-server/storage/logs/NormalizeWorker.log || true
   ```

Webhook smoke test
- From your Mac (forces host to VM IP; update domain if needed):
  ```bash
  API_DOMAIN="v2pixel-api.thynkdata.com"
  curl -sS -o - -w "\nHTTP:%{http_code}\n" \
    --resolve "$API_DOMAIN:80:35.196.36.229" \
    -H "Content-Type: application/json" \
    -X POST "http://$API_DOMAIN/webhook.php" \
    -d '{"event":"deploy","ts":"'"$(date -u +"%Y-%m-%dT%H:%M:%SZ")"'"}"
  ```
- On the VM, verify logs:
  ```bash
  sudo tail -n 30 /var/www/pixel-v2-server/storage/logs/webhook.log
  ```

If Nginx config changed
```bash
sudo nginx -t && sudo systemctl reload nginx
```

Database migrations (only when needed)
- Do NOT source .env into the shell. Read values safely and run mysql without printing secrets.
- On the VM:
  ```bash
  DB_HOST="$(sudo sed -n 's/^DB_HOST=//p' /var/www/pixel-v2-server/.env | tr -d '\r')"
  DB_USER="$(sudo sed -n 's/^DB_USER=//p' /var/www/pixel-v2-server/.env | tr -d '\r')"
  DB_PASS="$(sudo sed -n 's/^DB_PASS=//p' /var/www/pixel-v2-server/.env | tr -d '\r')"
  DB_NAME="$(sudo sed -n 's/^DB_NAME=//p' /var/www/pixel-v2-server/.env | tr -d '\r')"

  # Example: apply a SQL file you just deployed
  # Replace path.sql with your actual migration file path
  MYSQL_PWD="$DB_PASS" mysql --protocol=TCP -h "$DB_HOST" -u "$DB_USER" -D "$DB_NAME" < /var/www/pixel-v2-server/migrations/path.sql
  ```

Rollback
- Show recent commits on the VM and switch; then restart worker.
```bash
sudo -u www-data git -C /var/www/pixel-v2-server log --oneline -n 5
sudo -u www-data git -C /var/www/pixel-v2-server checkout <commit>
sudo systemctl restart php-worker@NormalizeWorker
# To return to tip of main later:
sudo -u www-data git -C /var/www/pixel-v2-server checkout main
sudo -u www-data git -C /var/www/pixel-v2-server reset --hard origin/main
sudo systemctl restart php-worker@NormalizeWorker
```

Worker notes
- Service definition is set to run /var/www/pixel-v2-server/src/Workers/NormalizeWorker.php.
- The worker loads DB config from /var/www/pixel-v2-server/.env via the systemd unit override.
- Check status/logs quickly:
  ```bash
  systemctl --no-pager --full status php-worker@NormalizeWorker || true
  sudo tail -n 50 /var/www/pixel-v2-server/storage/logs/NormalizeWorker.log || true
  ```

TLS (later)
- After DNS for v2pixel-api.thynkdata.com points to 35.196.36.229:
  ```bash
  sudo apt-get update -y && sudo apt-get install -y certbot python3-certbot-nginx
  sudo certbot --nginx -d v2pixel-api.thynkdata.com
  ```

Gotchas / reminders
- Never source .env; read values with sudo+sed.
- Always run git operations for the server path as www-data to keep permissions consistent.
- Keep secrets out of your shell history.
- If Cloud SQL authentication fails (1045), align user/grants/password and restart the worker.
