# Cache Directory

This directory contains temporary cache files:

- `rate_limit_*.tmp` - Rate limiting data per IP address
- Other temporary cache files

## Cleanup
Cache files are automatically cleaned by the rate limiting system.
Consider adding a cleanup cron job for old cache files.
