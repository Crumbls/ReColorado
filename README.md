# ReColorado package.

This is an expansion of some work by Troy Davisson's PHPRETS package.  Long term goal would be to split the RETS system 
into it's own package and have this expand off of it with most likely a simple facade or repository to pull external data.
But until we have a better idea of what the system goals are, here we are.

There is plenty of code clean up to do here.  This should only handle getting data on demand.  Manipulation and extrapulation
are for the actual program.  Make sure to handle events appropriately.

This is in such an early place that we don't even know what we want, so I am just starting to spitball ideas.

Until it can really be built out, we need to determine it's importance.  We can not get in the weeds with this one.

## Examples
$listings = \ReColorado::getLatestResidential(50);
$listings = \ReColorado::getLatestCommercial(50);
# ReColorado
