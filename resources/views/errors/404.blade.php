<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="text-center px-6">
        <h1 class="text-9xl font-bold text-gray-300 dark:text-gray-600">404</h1>
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mt-4">Page Not Found</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">
            The page you are looking for does not exist or has been moved.
        </p>
        <a href="{{ url('/') }}"
           class="inline-block mt-6 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            Go Home
        </a>
    </div>
</body>
</html>
