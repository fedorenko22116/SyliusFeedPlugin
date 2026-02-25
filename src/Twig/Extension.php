<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Twig;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('setono_sylius_feed_remove_empty_tags', $this->removeEmptyTags(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('setono_sylius_feed_generate_feed_url', $this->generateFeedUrl(...)),
        ];
    }

    public function removeEmptyTags(string $xml): string
    {
        return (string) preg_replace('#<[^/>][^>]*></[^>]+>#', '', $xml);
    }

    public function generateFeedUrl(FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): string
    {
        $params = ['code' => $feed->getCode()];

        // Only pass _locale if the route has it as a parameter
        if ($this->urlGenerator instanceof RouterInterface) {
            $route = $this->urlGenerator->getRouteCollection()->get('setono_sylius_feed_shop_feed_show');
            if (null !== $route && str_contains($route->getPath(), '{_locale}')) {
                $params['_locale'] = $locale->getCode();
            }
        }

        $path = $this->urlGenerator->generate('setono_sylius_feed_shop_feed_show', $params);

        // todo maybe inject request context into router instead to 'make it right'
        return sprintf('%s://%s%s', $this->getScheme(), (string) $channel->getHostname(), $path);
    }

    private function getScheme(): string
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return 'https';
        }

        return $request->getScheme();
    }
}
