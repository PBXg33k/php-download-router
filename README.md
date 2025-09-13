# PHP Download Router


An API Platform powered API service which allows downloading files using various backends.
It routes download requests to the following backends based on their supported domains:
- yt-dlp (YouTube and many other video platforms)
- gallery-dl (Image hosting platforms)

More to be added in the future.






```javascript

javascript: (function() {
    var url = "https://${host}/download_jobs",
        newTab = window.open(url, "_blank"),
        f = newTab.document.createElement("form");
    f.action = url;
    f.method = "POST";
    var i = newTab.document.createElement("input");
    i.name = "url";
    i.type = "hidden";
    i.value = window.location.href;
    f.appendChild(i);
    newTab.document.body.appendChild(f);
    f.submit();
})();

```
