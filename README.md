# PMHopper
A PocketMine-MP plugin implementing vanilla hopper behaviour.

## Optimizations
A few optimizations were made to account for idle hoppers ticking unnecessarily.
- A hopper will continue to tick itself every `transfer-cooldown` (default: 8) ticks only if there is a container above it or on the side it's tube is facing.
- A hopper will continue to tick itself every `items-sucking-tick-rate` (default: 1) ticks only if there is no container above it preventing it from sucking items.

Currently, container <-> hopper transfers and item entity -> hopper transfers are two isolated queues. As per the vanilla behaviour, a hopper transfers items between containers every 8 ticks and sucks items as quickly as 1 tick.

Reconfiguring some variables may help reduce server load. For example, the default configuration values for `transfer-cooldown` and `items-sucked` reflect vanilla.
```yaml
transfer-cooldown: 8
items-sucked: 1
```
The default configuration tries every 8 ticks to sucks 1 item from a container above it. Changing it to suck 3 items every 24 ticks will decrease the number of times a hopper gets ticked.
