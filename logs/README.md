# Logs Directory

This directory contains application logs:

- `api_errors.log` - API error logs with detailed context
- PHP error logs may also be written here

## Log Format
```
[TIMESTAMP] HTTP CODE: MESSAGE | User: USER_ID | IP: IP_ADDRESS | URI: REQUEST_URI | UA: USER_AGENT [FILE:LINE]
```

## Log Rotation
Consider implementing log rotation to prevent files from growing too large.
