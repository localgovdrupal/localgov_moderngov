<?php

namespace Drupal\localgov_moderngov\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Post processing for the ModernGov template page.
 *
 * Processing:
 * - Converts all relative URLs in the page to absolute URLs.
 */
class HtmlResponseSubscriber implements EventSubscriberInterface {

  /**
   * Convert all relative URLs to absolute.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {

    $request = $event->getRequest();
    $route_name = $request->get('_route');
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse || $route_name !== 'localgov_moderngov.modern_gov') {
      return;
    }

    $request_scheme_and_host = $request->getSchemeAndHttpHost();

    $html = $response->getContent();
    $html_with_absolute_urls = self::transformRootRelativeUrlsToAbsolute($html, $request_scheme_and_host);

    $response->setContent($html_with_absolute_urls);
  }

  /**
   * Converts all root-relative URLs to absolute URLs.
   *
   * Based on
   * Drupal\Component\Utility\Html::transformRootRelativeUrlsToAbsolute()
   * which processes Html *body* content only.  Whereas here, we process the
   * entire HTML document.
   *
   * @param string $html
   *   The partial (X)HTML snippet to load. Invalid markup will be corrected on
   *   import.
   * @param string $scheme_and_host
   *   The root URL, which has a URI scheme, host and optional port.
   *
   * @return string
   *   The updated (X)HTML snippet.
   *
   * @see Drupal\Component\Utility\Html::transformRootRelativeUrlsToAbsolute()
   */
  public static function transformRootRelativeUrlsToAbsolute($html, $scheme_and_host) :string {

    $html_dom = new \DOMDocument();
    // Ignore warnings during HTML soup loading.
    @$html_dom->loadHTML($html);
    $xpath = new \DOMXpath($html_dom);

    $uriAttributes = [
      'href', 'poster', 'src', 'cite', 'data',
      'action', 'formaction', 'srcset', 'about',
    ];

    // Update all root-relative URLs to absolute URLs in the given HTML.
    foreach ($uriAttributes as $attr) {
      foreach ($xpath->query("//*[starts-with(@$attr, '/') and not(starts-with(@$attr, '//'))]") as $node) {
        $node->setAttribute($attr, $scheme_and_host . $node->getAttribute($attr));
      }
      foreach ($xpath->query("//*[@srcset]") as $node) {
        // @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-srcset
        // @see https://html.spec.whatwg.org/multipage/embedded-content.html#image-candidate-string
        $image_candidate_strings = explode(',', $node->getAttribute('srcset'));
        $image_candidate_strings = array_map('trim', $image_candidate_strings);
        for ($i = 0; $i < count($image_candidate_strings); $i++) {
          $image_candidate_string = $image_candidate_strings[$i];
          if ($image_candidate_string[0] === '/' && $image_candidate_string[1] !== '/') {
            $image_candidate_strings[$i] = $scheme_and_host . $image_candidate_string;
          }
        }
        $node->setAttribute('srcset', implode(', ', $image_candidate_strings));
      }
    }

    $html_with_absolute_urls = $html_dom->saveHtml();
    return $html_with_absolute_urls;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // This event subscriber needs to know about the route so it needs to have
    // a priority of 31 or less. To modify headers a priority of 0 or less is
    // needed.
    $events[KernelEvents::RESPONSE][] = ['onRespond', -10];

    return $events;
  }

}
