<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>COACHTECHフリマ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header__left">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/logo.svg') }}" alt="サイトロゴ">
                </a>
            </div>

            @auth
                <div class="header__center">
                    <form class="search-form" method="get">
                        <input class="search" type="text" name="search" id="search" placeholder="なにをお探しですか？">
                    </form>
                </div>
                <div class="header__right">
                    <nav>
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button class="logout-button__submit" type="submit">ログアウト</button>
                        </form>
                        <a href="{{ route('mypage') }}">マイページ</a>
                        <a href="{{ route('sell') }}" class="sell-button">出品</a>
                    </nav>
                </div>
            @endauth
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>

</html>
