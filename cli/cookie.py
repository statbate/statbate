#!/usr/bin/env python

import cloudscraper
import json

scraper = cloudscraper.create_scraper(
  interpreter='nodejs',
  recaptcha={
    'provider': '2captcha',
    'api_key': ''
  }
)
scraper.get("https://chaturbate.com")

x = scraper.cookies.get_dict()
x["ua"] = scraper.headers
print(json.dumps(x))
