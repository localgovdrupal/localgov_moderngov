<?php

namespace Drupal\localgov_moderngov\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Post processing for the ModernGov template page.
 *
 * Processing:
 * - Converts all relative URLs in the page to absolute URLs.
 * - Empties the main tag when the `nocontent` HTTP query parameter is present.
 * - Returns the `header` tag and its children when the `header` HTTP query
 *   parameter is present.
 * - Returns the `footer` tag and its children when the `footer` HTTP query
 *   parameter is present.
 *
 * @todo Both the `header` and `footer` can happen more than once in a page.  At
 *       the moment we only process the very first of these.  This needs fixing.
 */
class HtmlResponseSubscriber implements EventSubscriberInterface {

  /**
   * Alters browser-bound page content.
   *
   * Converts all relative URLs to absolute.  Then a few more alterations as
   * mentioned in the class comment above.
   */
  public function onRespond(ResponseEvent $event) {

    $request = $event->getRequest();
    $has_content_modifier_req = !is_null($request->get('nocontent'));
    $has_header_req = !is_null($request->get('header'));
    $has_footer_req = !is_null($request->get('footer'));
    $route_name = $request->get('_route');
    $response = $event->getResponse();

    if (!$response instanceof HtmlResponse || $route_name !== 'localgov_moderngov.modern_gov') {
      return;
    }

    $html = $response->getContent();
    $html_dom = self::toDom($html);

    $request_scheme_and_host = $request->getSchemeAndHttpHost();
    $html_dom_with_absolute_urls = self::transformRootRelativeUrlsToAbsolute($html_dom, $request_scheme_and_host);

    if ($has_content_modifier_req) {
      $html_dom_with_absolute_urls = self::emptyContent($html_dom_with_absolute_urls);
    }

    if ($has_header_req) {
      $header_elem_list = $html_dom_with_absolute_urls->getElementsByTagName('header');
      $resultant_html = self::toHtml($html_dom_with_absolute_urls, $header_elem_list->item(0));
    }
    elseif ($has_footer_req) {
      $footer_elem_list = $html_dom_with_absolute_urls->getElementsByTagName('footer');
      $resultant_html = self::toHtml($html_dom_with_absolute_urls, $footer_elem_list->item(0));
    }
    else {
      $resultant_html = self::toHtml($html_dom_with_absolute_urls);
    }

    $response->setContent($resultant_html);
  }

  /**
   * Converts all root-relative URLs to absolute URLs.
   *
   * Based on
   * Drupal\Component\Utility\Html::transformRootRelativeUrlsToAbsolute()
   * which processes Html *body* content only.  Whereas here, we process the
   * entire HTML document.
   *
   * @param \DOMDocument $html_dom
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
  public static function transformRootRelativeUrlsToAbsolute(\DOMDocument $html_dom, $scheme_and_host): \DOMDocument {

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

    return $html_dom;
  }

  /**
   * Empties page content.
   *
   * Note that "A document mustn't have more than one <main> element that
   * doesn't have the hidden attribute specified."  In case of multiple
   * visible main elements in a page, we empty only the very first one.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/main#try_it
   */
  public static function emptyContent(\DOMDocument $html_dom) :\DOMDocument {

    $main_elem_list = $html_dom->getElementsByTagName('main');
    $visible_main_elem_list = array_filter(iterator_to_array($main_elem_list), fn(\DOMElement $elem) => !$elem->hasAttribute('hidden'));
    if (empty($visible_main_elem_list)) {
      return $html_dom;
    }

    $main_elem = current($visible_main_elem_list);
    $mains_children = [];
    foreach ($main_elem->childNodes as $child) {
      $mains_children[] = $child;
    }
    foreach ($mains_children as $child) {
      $main_elem->removeChild($child);
    }

    return $html_dom;
  }

  /**
   * HTML string to DOM.
   */
  public static function toDom(string $html): \DOMDocument {

    $html_dom = new \DOMDocument();
    // Ignore warnings during HTML soup loading.
    @$html_dom->loadHTML($html);

    return $html_dom;
  }

  /**
   * HTML DOM to string.
   */
  public static function toHtml(\DOMDocument $html_dom, ?\DOMNode $node = NULL): string {

    $html = $html_dom->saveHtml($node);
    return $html;
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
