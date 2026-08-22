---
paths:
  - 'resources/views/pages/**'
---

# Pages

## Guest anonymity is the product's core promise
Guests are told in the cookie banner that Emma and Anders learn only THAT an item is claimed, never by whom. Nothing may surface guest_id, a device token or its hash, or anything that links a claim to a person — not on the public list, and not in the admin, which shows claim counts only.

Guests are identified by a random token held on their own device; only the SHA-256 digest is stored (guest_tokens.token_hash), and IPs are only ever kept salted-hashed. A guest may hold several tokens (one per device) so recognising them somewhere new never locks the first device out. There are tests asserting the leak-free rendering in WishlistPageTest and ManageItemsTest — keep them passing.
