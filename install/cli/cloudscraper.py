#!/usr/bin/env python3

import cloudscraper
import sys

scraper = cloudscraper.create_scraper(
  delay=15,
  interpreter='nodejs',
  captcha={
    'provider': '2captcha',
    'api_key': ''
  }
)

if len(sys.argv) < 2:
    print('no url'); 
    exit(0)

print(scraper.get(sys.argv[1]).text)
