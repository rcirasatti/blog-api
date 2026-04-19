# Laravel Blog API with Octane + RoadRunner Setup Guide

## ✅ Completed Steps

### 1. **Code Compatibility ✓**
Your API code is **fully compatible** with Octane. No changes needed!
- All models use clean instance properties (no global state)
- Controllers are stateless CRUD operations
- Policies are pure authorization logic
- No Octane-unsafe patterns detected

### 2. **Octane Installation ✓**
- ✓ Laravel Octane v2.17.1 installed
- ✓ RoadRunner HTTP v4.1.0 installed
- ✓ RoadRunner CLI v2.7.2 installed

### 3. **Configuration Files Created ✓**
- `config/octane.php` - Octane configuration
- `rr.yaml` - RoadRunner server configuration
- `docker-compose.yml` - Docker setup for local development
- `Dockerfile.octane` - Docker image for production
- `nginx.conf` - Nginx reverse proxy configuration
- `.env.example` - Updated with Octane & Redis environment variables

---

## 🚀 Quick Start

### **Option 1: Local Development (Recommended)**

#### Prerequisites
- PHP 8.2+
- Redis (optional, for caching)
- Composer

#### 1. Install Dependencies
```bash
composer install
```

#### 2. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

#### 3. Configure Cache for Redis (Optional)
Edit `.env`:
```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### 4. Start Octane Server
```bash
# Basic start on localhost:8000
php artisan octane:start --server=roadrunner

# With custom host/port
php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000

# With workers and max requests
php artisan octane:start --server=roadrunner --workers=4 --max-requests=500

# With watch mode (auto-reload on code changes)
php artisan octane:start --server=roadrunner --watch
```

The API will be available at: `http://localhost:8000/api/...`

---

### **Option 2: Docker Compose (Complete Stack)**

Start all services (API, Nginx, Redis):
```bash
docker-compose up -d
```

This will:
- Start Octane/RoadRunner on `localhost:8000`
- Start Nginx reverse proxy on `localhost:80`
- Start Redis on `localhost:6379`

Check logs:
```bash
docker-compose logs -f blog-api
```

Stop services:
```bash
docker-compose down
```

---

## 📝 Configuration Details

### RoadRunner Configuration (`rr.yaml`)
- **Workers**: Auto-scaled based on CPU cores
- **Max Jobs**: 64 concurrent requests per worker
- **Port**: 8000 (default)
- **Request Size**: 32MB max

### Octane Configuration (`config/octane.php`)
- **Server**: RoadRunner
- **Port**: 8000
- **Host**: 0.0.0.0 (listen on all interfaces)
- **Workers**: Auto (uses available CPU cores)
- **Max Requests**: 500 (reload worker after 500 requests to prevent memory leaks)
- **Config Path**: `base_path('rr.yaml')`

### Nginx Configuration (`nginx.conf`)
- **Upstream**: RoadRunner on 127.0.0.1:8000
- **Static Files**: Served directly (CSS, JS, images)
- **Dynamic Routes**: Proxied to RoadRunner
- **Security Headers**: CORS, CSP, X-Frame-Options enabled
- **Compression**: Gzip enabled
- **Client Size**: 100MB max upload

### Redis Configuration (`.env`)
```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CLIENT=phpredis
```

---

## 🔍 Testing

### 1. Health Check
```bash
curl http://localhost:8000/api/health
```

### 2. API Endpoints
```bash
# Get all posts
curl http://localhost:8000/api/posts

# Create post (requires auth token)
curl -X POST http://localhost:8000/api/posts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test","content":"Test post"}'
```

### 3. Monitor Performance
Watch Octane logs:
```bash
php artisan octane:start --server=roadrunner --log-level=debug
```

Check Redis:
```bash
redis-cli
> INFO
> KEYS *
```

---

## ⚡ Performance Tips

### 1. **Optimize Worker Count**
```env
OCTANE_WORKERS=4    # Default: auto (CPU cores)
```
- Set to number of CPU cores for best performance
- Too many workers = wasted memory
- Too few workers = underutilization

### 2. **Tune Max Requests**
```env
OCTANE_MAX_REQUESTS=500    # Default: 500
```
- Lower = more frequent reloads (fresh state, less memory leaks)
- Higher = less overhead, but risk of memory accumulation
- Recommended: 500-1000

### 3. **Enable Redis Caching**
```env
CACHE_STORE=redis
```
- Much faster than database cache
- Persists across worker reloads
- Use for session, query cache, etc.

### 4. **Connection Pooling**
RoadRunner automatically handles:
- Database connection pooling
- Persistent HTTP connections
- Request/response buffering

---

## 🐛 Troubleshooting

### **Issue: "Undefined constant SIGINT" on Windows**
**Cause**: Windows doesn't support POSIX signals (SIGINT, SIGTERM, SIGHUP) that Octane requires.

**Solutions**:

#### Option 1: Use WSL2 (Windows Subsystem for Linux 2) - **RECOMMENDED**
```bash
# Install WSL2 with Ubuntu
wsl --install -d Ubuntu

# Inside WSL Ubuntu terminal
cd /mnt/c/path/to/blog-api
php artisan octane:start --server=roadrunner
```

#### Option 2: Use Docker (Any Platform)
```bash
# Start all services
docker-compose up -d

# Check logs
docker-compose logs -f blog-api
```

#### Option 3: Use Swoole Server (Windows Compatible)
```bash
composer require laravel/octane --with-dependencies
composer require swoole/swoole --ignore-platform-req=ext-sockets

# Start with Swoole
php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
```

#### Option 4: Use Git Bash or Cygwin
These provide better Unix compatibility on Windows.

### **Issue: "There are no commands defined in the octane namespace"**
**Solution**: Ensure `OctaneServiceProvider` is registered in `bootstrap/providers.php`:
```php
return [
    App\Providers\AppServiceProvider::class,
    Laravel\Octane\OctaneServiceProvider::class,  // ← Add this
];
```

### **Issue: "ext-sockets" extension not available**
**Solution**: Enable sockets extension in `php.ini`:
```ini
extension=sockets
```
Or install with: `php composer require ... --ignore-platform-req=ext-sockets`

### **Issue: Memory usage keeps growing**
**Solution**: Lower `max_requests` to force worker reloads:
```env
OCTANE_MAX_REQUESTS=500    # or lower: 200, 300
```

### **Issue: Redis connection errors**
**Solution**: Ensure Redis is running:
```bash
redis-cli ping
# Expected output: PONG

# If not running, start Redis:
# Windows: redis-server.exe
# Docker: docker run -d -p 6379:6379 redis:alpine
```

### **Issue: Nginx cannot connect to RoadRunner**
**Solution**: Ensure Octane is running and listening:
```bash
netstat -an | findstr 8000    # Windows
lsof -i :8000                 # macOS/Linux
```

---

## 📊 Monitoring & Logs

### View Octane Logs
```bash
php artisan octane:start --server=roadrunner --log-level=debug
```

### View RoadRunner Metrics
Add to `rr.yaml`:
```yaml
metrics:
  listen: "localhost:2112"
```

Then access: `http://localhost:2112/metrics`

### Monitor Database Connections
```bash
# Check active connections
sqlite3 database/database.sqlite "SELECT COUNT(*) FROM sqlite_master;"
```

---

## 🔐 Security Notes

1. **Environment Variables**: Keep `.env` secure (never commit)
2. **CORS**: Update `nginx.conf` with your actual domain
3. **SSL/TLS**: Uncomment HTTPS section in `nginx.conf`
4. **Rate Limiting**: Configure in middleware if needed
5. **API Keys**: Use `laravel/sanctum` for token management

---

## 📦 Production Deployment

### 1. **Build Docker Image**
```bash
docker build -f Dockerfile.octane -t blog-api:latest .
```

### 2. **Run with Docker**
```bash
docker run -d \
  -p 8000:8000 \
  -e CACHE_STORE=redis \
  -e REDIS_HOST=redis \
  -e APP_DEBUG=false \
  --name blog-api \
  blog-api:latest
```

### 3. **Use Docker Compose**
```bash
docker-compose -f docker-compose.yml up -d
```

### 4. **Health Check**
```bash
curl -f http://localhost:8000/api/health || exit 1
```

---

## 📚 Useful Commands

```bash
# Start Octane with watch mode (dev)
php artisan octane:start --server=roadrunner --watch

# Start with specific workers
php artisan octane:start --server=roadrunner --workers=8

# Run migrations before start
php artisan migrate
php artisan octane:start --server=roadrunner

# Test API endpoint
php artisan tinker
>>> Http::get('http://localhost:8000/api/posts')

# Reload workers gracefully
php artisan octane:reload

# Stop Octane (Ctrl+C in terminal)
```

---

## ✨ What's Next?

1. **Add API tests** to verify Octane compatibility
2. **Monitor performance** with load testing tools (Apache Bench, wrk)
3. **Setup CI/CD** to build and deploy Docker images
4. **Configure logging** for production
5. **Enable SSL/TLS** with Nginx

---

## 📖 References

- [Laravel Octane Documentation](https://laravel.com/docs/octane)
- [RoadRunner Documentation](https://roadrunner.dev)
- [Redis Documentation](https://redis.io/docs)
- [Nginx Reverse Proxy](https://nginx.org/en/docs/http/ngx_http_proxy_module.html)

