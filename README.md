# EPG Scraper API Framework

This is a database-free EPG Scraper API Framework written in PHP. It acts as an API endpoint that parses TV schedule data from various sources (like HTML pages or APIs).

## Prerequisites

- **PHP 8.0** or higher must be installed and accessible in your system's PATH.
- (Optional) **Composer** if you plan to add dependencies in the future.

## How to Run Locally

You can use PHP's built-in web server to easily run this project locally.

1. Open your terminal or PowerShell.
2. Navigate to the project directory:
   ```powershell
   cd c:\Projects\epg
   ```
3. Start the PHP server on port 8000:
   ```powershell
   php -S localhost:8000
   ```

The API is now running locally at `http://localhost:8000`.

## Testing the Application

The main entry point is `index.php` and it expects a `channel` query parameter. The channels are defined in `config/channels.php`.

Here are a few examples of how to query the API:

### Test the Dummy API
To test the pre-configured dummy API parser, open your browser or use a tool like `curl` / `Invoke-WebRequest`:
```
http://localhost:8000/?channel=dummy_api
```

### Test the Iran International Parser
To test the actual scraper for Iran International:
```
http://localhost:8000/?channel=iran_intl
```

### Response Format
A successful response will return a JSON array containing the programs:
```json
[
  {
    "title": "Program Title",
    "start_time": "2026-06-10T12:00:00+00:00",
    "end_time": "2026-06-10T12:30:00+00:00"
  }
]
```

## Troubleshooting
- **Missing channel parameter**: If you do not provide `?channel=...`, you will get a 400 Bad Request error.
- **Channel not found**: If you request a channel not present in `config/channels.php`, you will get a 404 Not Found error.
- **Empty Array `[]`**: This means the parser either encountered an error while fetching the upstream data, or the response from the upstream was empty. Check your internet connection or the parser logic if this persists.
