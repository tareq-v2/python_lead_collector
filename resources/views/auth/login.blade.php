<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Agency Lead DB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E3A5F',
                        accent: '#2E75B6'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md">

        {{-- Logo / Header --}}
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🌐</div>
            <h1 class="text-2xl font-bold text-primary">Agency Lead DB</h1>
            <p class="text-gray-500 text-sm mt-1">Sign in to access your dashboard</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-8">

            <form method="POST" action="/login">
                @csrf

                {{-- Email --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent
                               @error('email') border-red-400 bg-red-50 @enderror"
                        placeholder="you@example.com">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent
                               @error('password') border-red-400 bg-red-50 @enderror"
                        placeholder="••••••••">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center mb-6">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 accent-accent rounded">
                        Keep me signed in
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full bg-primary text-white py-2.5 rounded-lg text-sm font-semibold
                               hover:bg-blue-900 transition focus:outline-none focus:ring-2 focus:ring-accent">
                    Sign In →
                </button>

            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            Private tool — access restricted to owner only
        </p>
    </div>

</body>

</html>
