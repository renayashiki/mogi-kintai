@extends('layouts.guest')

@section('title', '会員登録')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
    <div class="auth-content">
        <h1 class="content-title">会員登録</h1>
        <form method="POST" action="{{ route('register') }}" class="register-form" novalidate>
            @csrf
            <div class="form-group">
                <label for="name" class="form-label">名前</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required
                    autofocus>
                @error('name')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required>
                @error('email')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input type="password" id="password" name="password" class="form-input" required>
                @error('password')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-label">パスワード確認</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required>
                @error('password_confirmation')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="submit-btn">登録する</button>
        </form>
        <p class="register-link-area">
            <a href="{{ route('login') }}" class="register-link">ログインはこちら</a>
        </p>
    </div>
@endsection
