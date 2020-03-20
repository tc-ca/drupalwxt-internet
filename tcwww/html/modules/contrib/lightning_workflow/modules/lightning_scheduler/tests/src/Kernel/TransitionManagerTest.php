<?php

namespace Drupal\Tests\lightning_scheduler\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\lightning_scheduler\TransitionManager;

/**
 * @coversDefaultClass \Drupal\lightning_scheduler\TransitionManager
 *
 * @group lightning
 * @group lightning_workflow
 * @group lightning_scheduler
 */
class TransitionManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'datetime',
    'lightning_scheduler',
    'system',
  ];

  /**
   * @covers ::validate
   *
   * @dataProvider providerValidate
   */
  public function testValidate($value, $expected_error = NULL) {
    $element = [
      '#value' => Json::encode($value),
      '#name' => 'test_element',
      '#parents' => ['test_element'],
    ];
    $form_state = new FormState();

    $form_state->setFormObject($this->prophesize(FormInterface::class)->reveal());

    TransitionManager::validate($element, $form_state);

    $errors = $form_state->getErrors();
    $errors = array_map('strval', $errors);
    $errors = array_map('strip_tags', $errors);

    if ($expected_error) {
      $this->assertContains($expected_error, $errors);
    }
    else {
      $this->assertEmpty($errors);
    }
  }

  /**
   * Data provider for ::testValidate().
   *
   * @return array[]
   *   Sets of arguments to pass to ::testValidate().
   */
  public function providerValidate() {
    return [
      'empty string' => [
        '',
        'Expected scheduled transitions to be an array.',
      ],
      'null' => [
        NULL,
        'Expected scheduled transitions to be an array.',
      ],
      'boolean false' => [
        FALSE,
        'Expected scheduled transitions to be an array.',
      ],
      'boolean true' => [
        TRUE,
        'Expected scheduled transitions to be an array.',
      ],
      'random string' => [
        $this->randomString(128),
        'Expected scheduled transitions to be an array.',
      ],
      'integer' => [
        123,
        'Expected scheduled transitions to be an array.',
      ],
      'float' => [
        123.45,
        'Expected scheduled transitions to be an array.',
      ],
      'empty array' => [
        [],
      ],
      'time, no date' => [
        [
          'when' => '08:57',
        ],
        'Scheduled transitions must have a date and time.',
      ],
      'date, no time' => [
        [
          [
            'state' => 'fubar',
            'when' => '1984-09-19',
          ],
        ],
        '"1984-09-19" is not a valid date and time.',
      ],
      'date and time' => [
        [
          [
            'when' => '1938-37-12 08:57',
          ],
        ],
        '"1938-37-12 08:57" is not a valid date and time.',
      ],
      'date as float' => [
        [
          [
            'state' => 'fubar',
            'when' => '123.45',
          ],
        ],
        '"123.45" is not a valid date and time.',
      ],
      'valid different time stamps, invalid order' => [
        [
          [
            'state' => 'fubar',
            'when' => mktime(15, 42, 0, 11, 5, 2018),
          ],
          [
            'state' => 'fubar',
            'when' => mktime(2, 30, 0, 9, 4, 2018),
          ],
        ],
        "You cannot schedule a transition to take place before 3:42 PM on November 5, 2018.",
      ],
      'valid same dates, valid times, invalid order' => [
        [
          [
            'state' => 'fubar',
            'when' => mktime(6, 30, 0, 9, 19, 2022),
          ],
          [
            'state' => 'fubar',
            'when' => mktime(4, 46, 0, 9, 19, 2022),
          ],
        ],
        "You cannot schedule a transition to take place before 6:30 AM on September 19, 2022.",
      ],
      'valid different dates' => [
        [
          [
            'state' => 'fubar',
            'when' => mktime(2, 30, 0, 9, 4, 2022),
          ],
          [
            'state' => 'fubar',
            'when' => mktime(15, 42, 0, 11, 5, 2022),
          ],
        ],
      ],
      'valid same dates, different times' => [
        [
          [
            'state' => 'fubar',
            'when' => mktime(2, 30, 0, 9, 19, 2022),
          ],
          [
            'state' => 'fubar',
            'when' => mktime(15, 42, 0, 9, 19, 2022),
          ],
        ],
      ],
    ];
  }

}
