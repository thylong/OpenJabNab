# OpenJabNab Docker Setup Guide

This guide explains how to run OpenJabNab using Docker Compose for local development and Nabaztag device testing.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- A Nabaztag/Tag device (for testing)
- Network access between your computer and the Nabaztag

### 1. Start the Environment
```bash
./start-openjabnab.sh
```

This script will:
- Build the Docker containers
- Start all services (web, server, database)
- Copy bootcode files to the appropriate locations
- Display connection information

### 2. Access the Services

| Service | URL | Description |
|---------|-----|-------------|
| **Web Interface** | http://localhost | Main OpenJabNab web portal |
| **Admin Panel** | http://localhost/ojn_admin | Administrative interface |
| **phpMyAdmin** | http://localhost:8081 | Database management |
| **C++ Server** | localhost:8080 | HTTP API endpoint |
| **XMPP Server** | localhost:5222 | XMPP communication |

### 3. Configure Your Nabaztag

#### Step 1: Put Nabaztag in Adhoc Mode
1. Unplug the power adapter
2. Press and hold the button on the head
3. Plug in the power adapter
4. Release the button when the bunny turns blue

#### Step 2: Connect to Nabaztag WiFi
1. Connect your computer to the Nabaztag's WiFi network
2. Network name will be "NabaztagXX" (XX = last 2 digits of MAC address)

#### Step 3: Access Configuration
1. Open browser and go to: http://192.168.0.1/
2. Click "Click here to Start"
3. Click "Advanced configuration"
4. Scroll to "General Info" section

#### Step 4: Configure Server
1. Find the "Violet Platform" field
2. Change it to one of these options:
   - `localhost/vl` (if running on same machine as Docker)
   - `YOUR_IP_ADDRESS/vl` (replace with your computer's IP)
   - `nabaztag.local/vl` (if you set up local DNS)

#### Step 5: Apply Configuration
1. Click "Update and Start"
2. Wait for the Nabaztag to restart and connect

## 🏗️ Architecture

### Services
- **mysql**: MySQL 8.0 database
- **web**: PHP 8.1 + Apache web server
- **openjabnab**: C++ server for device communication  
- **phpmyadmin**: Database administration tool

### Ports
- `80`: Web interface
- `8080`: C++ server HTTP API
- `5222`: XMPP server
- `3306`: MySQL database (internal)
- `8081`: phpMyAdmin

### Volumes
- `mysql_data`: Persistent database storage
- Host directories mounted for live development

## 🛠️ Development

### File Structure
```
OpenJabNab/
├── docker-compose.yml          # Main orchestration
├── start-openjabnab.sh        # Startup script
├── docker/
│   ├── server/
│   │   ├── Dockerfile          # C++ server container
│   │   └── openjabnab.ini      # Server configuration
│   └── web/
│       ├── Dockerfile          # PHP web container
│       ├── apache-config.conf  # Apache virtual host
│       ├── config.php          # PHP database config
│       └── setup.sh            # Container initialization
├── server/                     # C++ source code
├── http-wrapper/              # PHP web interface
└── bootcode/                  # Nabaztag firmware files
```

### Managing the Environment

#### Start Services
```bash
docker-compose up -d
```

#### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f openjabnab
docker-compose logs -f web
docker-compose logs -f mysql
```

#### Restart Services
```bash
# All services
docker-compose restart

# Specific service
docker-compose restart openjabnab
```

#### Stop Services
```bash
docker-compose down
```

#### Rebuild Containers
```bash
docker-compose build --no-cache
docker-compose up -d
```

### Database Access

#### Using phpMyAdmin
- URL: http://localhost:8081
- Server: mysql
- Username: ojn
- Password: ojn

#### Using MySQL Client
```bash
docker-compose exec mysql mysql -u ojn -pojn ojn
```

### Debugging

#### Check Container Status
```bash
docker-compose ps
```

#### Access Container Shell
```bash
# C++ server container
docker-compose exec openjabnab bash

# Web container  
docker-compose exec web bash

# Database container
docker-compose exec mysql bash
```

#### Check Service Health
```bash
# Web service
curl http://localhost

# C++ server API
curl http://localhost:8080

# Database connection
docker-compose exec web php -r "echo 'DB: ' . (new mysqli('mysql', 'ojn', 'ojn', 'ojn'))->ping() ? 'OK' : 'FAILED';"
```

## 🔧 Configuration

### Environment Variables
You can customize the setup using environment variables:

```bash
# Database
export DB_HOST=mysql
export DB_NAME=ojn
export DB_USER=ojn  
export DB_PASS=ojn

# Server
export OJN_HTTP_HOST=localhost
export OJN_HTTP_PORT=8080
export OJN_DOMAIN=localhost
```

### Custom Configuration Files
- **C++ Server**: Edit `docker/server/openjabnab.ini`
- **Web Interface**: Edit `docker/web/config.php`
- **Apache**: Edit `docker/web/apache-config.conf`

## 🐛 Troubleshooting

### Common Issues

#### Container Won't Start
1. Check Docker is running: `docker version`
2. Check logs: `docker-compose logs [service]`
3. Verify ports aren't in use: `netstat -tlnp`

#### Nabaztag Can't Connect
1. Ensure your firewall allows connections on port 80
2. Verify the IP address you configured in the Nabaztag
3. Check that the device can reach your computer
4. Try using your computer's IP instead of localhost

#### Database Connection Issues
1. Wait for MySQL to fully start (check health status)
2. Verify credentials in configuration files
3. Check database logs: `docker-compose logs mysql`

#### Web Interface Not Loading
1. Check Apache logs: `docker-compose logs web`
2. Verify PHP configuration is correct
3. Ensure config.php was copied correctly

### Reset Everything
```bash
# Stop and remove all containers and volumes
docker-compose down -v
docker system prune -f

# Restart
./start-openjabnab.sh
```

## 📚 Additional Resources

- [Original OpenJabNab Documentation](README.md)
- [Nabaztag Setup Help](http://localhost/help/setup.php) (after starting)
- [Server Configuration Reference](server/conf/openjabnab.ini-dist)

---

Happy Nabaztag hacking! 🐰✨