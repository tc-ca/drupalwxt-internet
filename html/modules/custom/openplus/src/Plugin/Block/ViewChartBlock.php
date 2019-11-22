<?php

namespace Drupal\openplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a chart Block.
 *
 * @Block(
 *   id = "view_chart_block",
 *   admin_label = @Translation("View chart block"),
 *   category = @Translation("Openplus"),
 * )
 */
class ViewChartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $node = $this->getContextValue('node');
/*
  <head>
    <link rel="stylesheet" type="text/css" href="c3.css">
  </head>
  <body>
    <div id="chart"></div>

    <script src="d3.min.js" charset="utf-8"></script>
    <script src="c3.min.js"></script>
    <script>
var chart = c3.generate({
    bindto: '#chart',
    data: {
        x: 'Date', 
        x_Format: '%Y-%m-%d', 
        url: 'dates.csv', 
    },
    axis: {
    x: {
        type: 'timeseries', 
    }
}
});
*/

</script>
</body>

    $build['#markup'] = '<div></div>';

    return $build;
 
  }

}
