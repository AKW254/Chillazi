<?php
// Functions/scraper_summary.php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Scrape multiple URLs and return summarized content.
 *
 * @param array $urls   Array of URLs to scrape
 * @param string $apiKey Your OpenAI API key
 * @param int $limit   Character limit per page (default 2000)
 * @return string Summary text
 */
function scrapeAndSummarize(array $urls, int $limit = 2000): string
{
    $http = new Client();
    $content = "";
    foreach ($urls as $url) {
        try {
            $response = $http->get($url);
            $html = (string) $response->getBody();
            $crawler = new Crawler($html);

            // Remove script and style tags
            $crawler->filter('script, style')->each(function ($node) {
                foreach ($node as $n) {
                    $n->parentNode->removeChild($n);
                }
            });

            // Extract visible text from body
            $text = $crawler->filter('body')->text();
            $content .= substr($text, 0, $limit) . "\n\n";
        } catch (\Exception $e) {
            $content .= "Error scraping {$url}: " . $e->getMessage() . "\n\n";
        }
    }

    if (trim($content) === "") {
        return "Sorry, I could not fetch content from the provided pages.";
    }



    return  $content ?? "No summary generated.";
}
