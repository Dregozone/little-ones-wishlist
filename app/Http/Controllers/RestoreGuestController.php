<?php

namespace App\Http\Controllers;

use App\Actions\ManageCookieConsent;
use App\Actions\ResolveGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Restores a guest's list from the personal link they saved.
 *
 * This deliberately sits outside the name gate. A guest arriving from their own saved link
 * still has to answer the question if this browser has not seen it before, but their
 * identity is re-established first so the answer does not cost them their picks.
 */
class RestoreGuestController extends Controller
{
    /**
     * Recognise the guest behind a recovery token and send them back to the list.
     */
    public function __invoke(
        Request $request,
        string $token,
        ResolveGuest $resolve,
        ManageCookieConsent $consent,
    ): RedirectResponse {
        $guest = $resolve->guestForToken($token, $request);

        if ($guest === null) {
            // Flux toasts are dispatched from a Livewire component, so the message is
            // flashed here and raised by the list once it renders.
            return redirect()->route('home')->with('wishlist.toast', [
                'variant' => 'warning',
                'text' => __('We did not recognise that link. You can still browse the list and pick out gifts.'),
            ]);
        }

        // Following their own recovery link is itself an unambiguous request to be
        // remembered, so the identity cookie is granted along with a token for this device.
        $consent->store($request, true);

        if (! $guest->has_consented) {
            $guest->forceFill(['has_consented' => true])->save();
        }

        $resolve->issueToken($request, $guest);

        return redirect()->route('home')->with('wishlist.toast', [
            'variant' => 'success',
            'text' => __('Welcome back, your picks are highlighted again.'),
        ]);
    }
}
