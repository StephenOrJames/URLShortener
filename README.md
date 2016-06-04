# URLShortener
URLShortener is a URL shortener written in PHP.

## Creating redirects
The creation of redirects is handled by `URLShortener::createCode`. Each redirect will contain a `code` (the string used with the current domain for the redirection) and a `target` (the destination URL).

Before creating a new URL, the target is sanitized and validated with PHP's `filter_var` function.

If a custom redirect code is specified, it will be used (once it is available and valid, i.e. not too long, not too short, and contains only ASCII alphanumeric characters).
If a custom `code` was not spcified (i.e. the specified code is an empty string), however, a pre-existing redirect code will be recycled if available, or a new one will be created if not.

## Using redirects
The `index.php` file captures redirect codes through the `GET` parameter `r` (e.g. `https://example.com/index.php?r=C0de`, or, assuming your web server uses `index.php` as the default page, `https://example.com/?r=C0de`). 
If the code is mapped to a target URL in the database, the `Location` header will be changed to that URL, resulting in a 302 redirect. Otherwise, the user will have the opportunity to create a new shortened URL.

In theory, the URLs could be further simplified (or otherwise modified) by creating rewrite rules in your web server, which could possibly allow you to use URLs such as `https://example.com/C0de`.
