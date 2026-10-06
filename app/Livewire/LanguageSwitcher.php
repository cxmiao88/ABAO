<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $locale = 'en';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->locale) {
            $this->locale = $user->locale;
        } else {
            $this->locale = Session::get('locale', config('app.locale'));
        }
    }

    public function setLocale(string $locale): void
    {
        $supported = ['en', 'zh-cn'];

        if (! in_array($locale, $supported)) {
            return;
        }

        $this->locale = $locale;
        Session::put('locale', $locale);

        $user = Auth::user();
        if ($user) {
            $user->update(['locale' => $locale]);
        }

        // Full page reload so server-rendered translations apply everywhere.
        $this->redirect(url()->previous(), navigate: true);
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
