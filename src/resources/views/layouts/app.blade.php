<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>COACHTECHフリマ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

            @if (!Request::is('login') && !Request::is('register'))
                @auth
                    <div class="header__center">
                        <form action="{{ route('items.index') }}" class="search-form" method="get">
                            <input class="search" type="text" name="keyword" placeholder="なにをお探しですか？"
                                value="{{ request('keyword') }}">
                        </form>
                    </div>
                    <div class="header__right">
                        <nav>
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button class="logout-button__submit" type="submit">ログアウト</button>
                            </form>
                            <a href="{{ route('mypage') }}">マイページ</a>
                            <a href="{{ route('items.create') }}" class="sell-button">出品</a>
                        </nav>
                    </div>
                @endauth
                @guest
                    <div class="header__center">
                        <form action="{{ route('items.index') }}" class="search-form" method="get">
                            <input class="search" type="text" name="keyword" placeholder="なにをお探しですか？">
                        </form>
                    </div>
                    <div class="header__right">
                        <nav>
                            <a href="{{ route('login') }}">ログイン</a>
                            <a href="{{ route('login') }}">マイページ</a>
                            <a href="{{ route('login') }}" class="sell-button">出品</a>
                        </nav>
                    </div>
                @endguest
            @endif
        </div>
    </header>
    <main>
        @yield('content')
        @yield('script')
    </main>
</body>

</html>
