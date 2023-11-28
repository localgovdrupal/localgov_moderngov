<?php

declare(strict_types = 1);

namespace Drupal\localgov_moderngov;

/**
 * Grabs HTML header and footer markup.
 *
 * Extracts HTML markup for header, footer and associated scripts and styles.
 */
class HeaderFooterExtraction {

  /**
   * Header markup.
   *
   * Includes header, all scripts and link[rel=stylesheet] markup.
   *
   * Example output:
   * ```
   * <div class="scripts-n-links">
   *   <link rel="stylesheet">
   *   <link rel="stylesheet">
   *   <script src="https://example.net/script0.js"></script>
   *   <script src="https://example.net/script1.js"></script>
   * </div>
   *
   * <div class="pre-header-body-scripts">
   *   <script>alert('foo')</script>
   * </div>
   *
   * <header>
   *   ...
   * </header>
   * ```
   */
  public static function prepareHeader(\DOMDocument $html_dom): string {

    $header_list = $html_dom->getElementsByTagName('header');
    if (!$header_list->count()) {
      return '';
    }
    $header = self::toHtml($html_dom, $header_list->item(0));

    $xpath                 = new \DOMXpath($html_dom);
    $head_scripts_n_styles = self::extractHeadScriptsAndStyles($html_dom, $xpath);
    $other_scripts         = self::extractPreHeaderScripts($html_dom, $xpath);

    $result = $head_scripts_n_styles . PHP_EOL . $other_scripts . PHP_EOL . $header;
    $trimmed_result = trim($result);
    return $trimmed_result;
  }

  /**
   * Footer markup.
   *
   * Includes footer and any script tags that follow the footer tag.
   *
   * Sample output:
   * ```
   * <footer>
   * ...
   * </footer>
   *
   * <div class="post-footer-body-scripts">
   *   <script src="https://example.net/script2.js"></script>
   *   <script src="https://example.net/script3.js"></script>
   * </div>
   * ```
   */
  public static function prepareFooter(\DOMDocument $html_dom): string {

    $footer_list = $html_dom->getElementsByTagName('footer');
    if (!$footer_list->count()) {
      return '';
    }

    $footer = self::toHtml($html_dom, $footer_list->item(0));

    $xpath = new \DOMXpath($html_dom);
    $other_scripts = self::extractPostFooterScripts($html_dom, $xpath);

    $result = $footer . PHP_EOL . $other_scripts;
    $trimmed_result = trim($result);
    return $trimmed_result;
  }

  /**
   * Script and link[rel=stylesheet] tags from the head tag.
   */
  public static function extractHeadScriptsAndStyles(\DOMDocument $html_dom, \DOMXpath $xpath): string {

    return self::extractMarkup($html_dom, $xpath, '/html/head/script|/html/head/link[@rel="stylesheet"]', 'scripts-n-links');
  }

  /**
   * Script tags preceeded ing the header tag.
   */
  public static function extractPreHeaderScripts(\DOMDocument $html_dom, \DOMXpath $xpath): string {

    return self::extractMarkup($html_dom, $xpath, '/html/body//script[following::header]', 'pre-header-body-scripts');
  }

  /**
   * Script tags following the footer tag.
   */
  public static function extractPostFooterScripts(\DOMDocument $html_dom, \DOMXpath $xpath): string {

    return self::extractMarkup($html_dom, $xpath, '/html/body//script[preceding::footer]', 'post-footer-body-scripts');
  }

  /**
   * Extracts a chunk of a page.
   *
   * Extracts everything based on the given XPath query and return as an HTML
   * string wrapped in a wrapper div.
   */
  public static function extractMarkup(\DOMDocument $html_dom, \DOMXpath $xpath, $xpath_query, $wrapper_class): string {

    $tags = $xpath->query($xpath_query);
    if (!$tags->count()) {
      return '';
    }

    $wrapper_element = self::createEmptyDiv($html_dom, $wrapper_class);
    foreach ($tags as $dom_node) {
      $wrapper_element->append($dom_node);
    }

    return self::toHtml($html_dom, $wrapper_element);
  }

  /**
   * Creates a wrapper div.
   */
  public static function createEmptyDiv(\DOMDocument $html_dom, string $classname = ''): \DOMNode {

    $empty_div = $html_dom->createElement('div');
    if ($classname) {
      $empty_div->setAttribute('class', $classname);
    }

    return $empty_div;
  }

  /**
   * HTML DOM to string.
   */
  public static function toHtml(\DOMDocument $html_dom, ?\DOMNode $node = NULL): string {

    $html = $html_dom->saveHtml($node);
    return $html;
  }

}
