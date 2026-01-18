<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        @vite('resources/js/app.js')
        @vite('resources/css/app.css')
        @inertiaHead
        <style>
            html {
                scroll-behavior: smooth;
            }
        </style>
    </head>
    <body class="bg-gray-100 flex flex-col min-h-screen">
        @inertia
    </body>
</html>
