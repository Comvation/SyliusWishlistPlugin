<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusWishlistPlugin\Controller\Action;

use BitBag\SyliusWishlistPlugin\Command\Wishlist\UpdateWishlistName;
use BitBag\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class UpdateWishlistNameAction
{
    private MessageBusInterface $commandBus;

    private RequestStack $requestStack;

    private TranslatorInterface $translator;

    private WishlistRepositoryInterface $wishlistRepository;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        MessageBusInterface $commandBus,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        WishlistRepositoryInterface $wishlistRepository,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->commandBus = $commandBus;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->wishlistRepository = $wishlistRepository;
        $this->urlGenerator = $urlGenerator;
    }

    public function __invoke(Request $request): Response
    {
        $wishlistName = $request->get('edit_wishlist_name')['name'];
        Assert::string($wishlistName);
        $wishlistId = $request->attributes->getInt('id');
        $wishlist = $this->wishlistRepository->find($wishlistId);

        /** @var Session $session */
        $session = $this->requestStack->getSession();

        try {
            $updateWishlistName = new UpdateWishlistName($wishlistName, $wishlist);
            $this->commandBus->dispatch($updateWishlistName);

            $session->getFlashBag()->add(
                'success',
                $this->translator->trans('bitbag_sylius_wishlist_plugin.ui.wishlist_name_changed')
            );
        } catch (HandlerFailedException $exception) {
            $session->getFlashBag()->add(
                'error',
                $this->translator->trans('bitbag_sylius_wishlist_plugin.ui.wishlist_name_already_exists')
            );
        }
        return new Response($this->urlGenerator->generate('bitbag_sylius_wishlist_plugin_shop_wishlist_list_products'));
    }
}
