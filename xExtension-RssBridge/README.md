# RssBridge Extension

Sends added feeds through [RSS-Bridge](https://github.com/rss-bridge/rss-bridge), generating feeds for websites that don't have one. This allows FreshRSS to instantly support [hundreds of new sites](https://github.com/RSS-Bridge/rss-bridge/tree/master/bridges), e.g. Facebook and Twitter. It also works with RSS-Bridge instances that have token authentication.

## Configuration

* `rss_bridge_url`: the URL for an RSS-Bridge instance e.g. `https://example.com/rss-bridge/`
* `rss_bridge_token`: (optional) authentication token for secured RSS-Bridge instances

## Bridge Availability

Detection only works for bridges that are [whitelisted](https://rss-bridge.github.io/rss-bridge/For_Hosts/Whitelisting.html) in RSS-Bridge. The `detectParameters` function inside a bridge is what allows URLs to be detected; not all bridges support this. Website changes will sometimes break bridges, so make sure you're running the most recent version of RSS-Bridge and [open an issue](https://github.com/RSS-Bridge/rss-bridge/issues) if you're still having problems.

It's recommended that you [self-host RSS-Bridge](https://rss-bridge.github.io/rss-bridge/For_Hosts/Installation.html) so can enable all the bridges you want to use and ensure all bridges are up to date.

If you don't want to selfhost, here are some [publically available instances](https://rss-bridge.github.io/rss-bridge/General/Public_Hosts.html).

## Version History

Please see [changelog](CHANGELOG.md)

## Acknowledgements

This is based on DevonHess's excellent RssBridge extension. I added token authentication support and made other small improvements.
