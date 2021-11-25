<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My App</title>

</head>

<body>
    <form method="POST" action="{{ route('cart.textchange') }}">
        @csrf
        <label>Cart Button Text </label>
        <input type="text" name="cartTxt" />
        <input type="submit" value="Change" />
    </form>
    @if (Session::has('message'))
        {{ Session::get('message') }}
    @endif
    <script>
        let myVar = '';
    </script>
    <script src="{{ asset('js/myscript.js') }}"></script>
</body>

</html>
