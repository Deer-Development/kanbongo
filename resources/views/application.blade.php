<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="{{ asset('favicon.ico') }}" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kanbongo</title>
  <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
  @vite(['resources/js/main.js'])
</head>

<body>
<div id="app">
    <div id="loading-bg">
        <div class="loading-logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="60" height="60">
                <circle cx="50" cy="50" r="48" fill="#1466D4" />

                <rect x="20" y="25" width="15" height="50" rx="3" fill="#ffffff" />
                <circle cx="27.5" cy="30" r="3" fill="#1466D4" />
                <rect x="23" y="40" width="9" height="5" rx="2" fill="#1466D4" />
                <rect x="23" y="50" width="9" height="5" rx="2" fill="#1466D4" />

                <rect x="42.5" y="20" width="15" height="60" rx="3" fill="#ffffff" />
                <circle cx="50" cy="27" r="3" fill="#1466D4" />
                <rect x="45" y="37" width="10" height="5" rx="2" fill="#1466D4" />
                <rect x="45" y="47" width="10" height="5" rx="2" fill="#1466D4" />
                <rect x="45" y="57" width="10" height="5" rx="2" fill="#1466D4" />

                <rect x="65" y="25" width="15" height="50" rx="3" fill="#ffffff" />
                <circle cx="72.5" cy="30" r="3" fill="#1466D4" />
                <rect x="68" y="40" width="9" height="5" rx="2" fill="#1466D4" />
            </svg>


        </div>
        <div class=" loading">
            <div class="effect-1 effects"></div>
            <div class="effect-2 effects"></div>
            <div class="effect-3 effects"></div>
        </div>
    </div>
</div>

<script>
    const loaderColor = localStorage.getItem('kanban-initial-loader-bg') || '#FFFFFF'
    const primaryColor = localStorage.getItem('kanban-initial-loader-color') || '#141d35'

    if (loaderColor)
        document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
    if (loaderColor)
        document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

    if (primaryColor)
        document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
</script>
  </body>
</html>
