# DNS Configuration for Nabaztag Devices

This guide explains how to configure OpenJabNab for DNS-based access so that Nabaztag devices can connect using a proper domain name.

## Prerequisites

1. **DNS Record**: You need to create a DNS A record pointing your domain to your server's IP address:
   ```
   nabaztag.yourdomain.com.    A    YOUR_SERVER_IP
   ```

2. **Firewall**: Ensure these ports are accessible from the internet:
   - **Port 80**: HTTP web interface and device API
   - **Port 8080**: Direct C++ server API
   - **Port 5222**: XMPP for advanced device features

## Configuration Steps

### 1. Update Domain Configuration

Edit the `.env` file and replace `nabaztag.yourdomain.com` with your actual domain:

```bash
# Edit the .env file
nano .env

# Update these lines with your domain:
OJN_DOMAIN=your-actual-domain.com
OJN_HTTP_HOST=your-actual-domain.com
OJN_XMPP_HOST=your-actual-domain.com
```

### 2. Restart the Services

After updating the domain configuration:

```bash
# Rebuild and restart all services
docker-compose down
docker-compose build
docker-compose up -d
```

### 3. Verify Configuration

Check that services are running and configured correctly:

```bash
# Check service status
docker-compose ps

# Test web interface
curl http://your-actual-domain.com/

# Test API endpoint
curl http://your-actual-domain.com:8080/

# Check logs
docker-compose logs openjabnab
docker-compose logs web
```

## How It Works

The configuration system automatically updates:

1. **Apache Virtual Host**: Configures the web server to respond to your domain name
2. **PHP Configuration**: Updates all URL generation to use your domain
3. **OpenJabNab Server**: Configures the C++ server to use your domain for callbacks and XMPP
4. **API Endpoints**: All API responses include URLs with your domain

## Nabaztag Device Configuration

Configure your Nabaztag device to use your domain:

1. **Server URL**: Set to `http://your-actual-domain.com/`
2. **API Endpoint**: The device will automatically use the correct endpoints
3. **XMPP Server**: Will connect to `your-actual-domain.com:5222`

## Files Modified by Configuration

- `docker-compose.yml`: Environment variables
- `docker/web/config.php`: PHP constants
- `docker/server/openjabnab.ini`: C++ server settings
- `docker/web/apache-config.conf`: Apache virtual host
- Docker startup scripts automatically apply domain settings

## Troubleshooting

### Device Can't Connect
- Verify DNS record resolves: `nslookup your-actual-domain.com`
- Check firewall allows ports 80, 8080, 5222
- Test from external network: `curl http://your-actual-domain.com/`

### Services Won't Start
- Check Docker logs: `docker-compose logs`
- Verify .env file syntax
- Ensure no port conflicts on host system

### Wrong URLs in Web Interface
- Check `OJN_DOMAIN` environment variable
- Restart web container: `docker-compose restart web`
- Clear browser cache

## Security Considerations

For production use:
1. Consider setting up HTTPS with SSL certificates
2. Configure proper firewall rules
3. Set `OJN_DEBUG=false` in production
4. Use strong database passwords (change defaults)
5. Consider restricting database access (remove port 3306 exposure)

## Backup Configuration

Your configuration is stored in:
- `.env` file (domain settings)
- `mysql_data` volume (database)
- Any custom modifications to config files

Regular backups of these files will allow you to restore your setup.