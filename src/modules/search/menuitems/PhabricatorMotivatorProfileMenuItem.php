<?php
namespace orangins\modules\search\menuitems;

use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use yii\helpers\ArrayHelper;

final class PhabricatorMotivatorProfileMenuItem
  extends PhabricatorProfileMenuItem {

  const MENUITEMKEY = 'motivator';

  public function getMenuItemTypeIcon() {
    return 'fa-coffee';
  }

  public function getMenuItemTypeName() {
    return \Yii::t("app",'Motivator');
  }

  public function canAddToObject($object) {
    return ($object instanceof PhabricatorHomeApplication);
  }

  public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config) {

    $options = $this->getOptions();
    $name = ArrayHelper::getValue($options, $config->getMenuItemProperty('source'));
    if ($name !== null) {
      return \Yii::t("app",'Motivator: %s', $name);
    } else {
      return \Yii::t("app",'Motivator');
    }
  }

  public function buildEditEngineFields(
    PhabricatorProfileMenuItemConfiguration $config) {
    return array(
      (new PhabricatorInstructionsEditField())
        ->setValue(
          \Yii::t("app",
            'Motivate your team with inspirational quotes from great minds. '.
            'This menu item shows a new quote every day.')),
      (new PhabricatorSelectEditField())
        ->setKey('source')
        ->setLabel(\Yii::t("app",'Source'))
        ->setOptions($this->getOptions()),
    );
  }

  private function getOptions() {
    return array(
      'catfacts' => \Yii::t("app",'Cat Facts'),
    );
  }

  protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config) {

    $source = $config->getMenuItemProperty('source');

    switch ($source) {
      case 'catfacts':
      default:
        $facts = $this->getCatFacts();
        $fact_name = \Yii::t("app",'Cat Facts');
        $fact_icon = 'fa-paw';
        break;
    }

    $fact_text = $this->selectFact($facts);

    $item = $this->newItemView()
      ->setName($fact_name)
      ->setIcon($fact_icon)
      ->setTooltip($fact_text)
      ->setURI('#');

    return array(
      $item,
    );
  }

  private function getCatFacts() {
    return array(
      \Yii::t("app",'Cats purr when they are happy, upset, or asleep.'),
      \Yii::t("app",'The first cats evolved on the savannah about 8,000 years ago.'),
      \Yii::t("app",
        'Cats have a tail, two feet, between one and three ears, and two '.
        'other feet.'),
      \Yii::t("app",'Cats use their keen sense of smell to avoid feeling empathy.'),
      \Yii::t("app",'The first cats evolved in swamps about 65 years ago.'),
      \Yii::t("app",
        'You can tell how warm a cat is by examining the coloration: cooler '.
        'areas are darker.'),
      \Yii::t("app",
        'Cat tails are flexible because they contain thousands of tiny '.
        'bones.'),
      \Yii::t("app",
        'A cattail is a wetland plant with an appearance that resembles '.
        'the tail of a cat.'),
      \Yii::t("app",
        'Cats must eat a diet rich in fish to replace the tiny bones in '.
        'their tails.'),
      \Yii::t("app",'Cats are stealthy predators and nearly invisible to radar.'),
      \Yii::t("app",
        'Cats use a special type of magnetism to help them land on their '.
        'feet.'),
      \Yii::t("app",
        'A cat can run seven times faster than a human, but only for a '.
        'short distance.'),
      \Yii::t("app",
        'The largest recorded cat was nearly 11 inches long from nose to '.
        'tail.'),
      \Yii::t("app",
        'Not all cats can retract their claws, but most of them can.'),
      \Yii::t("app",
        'In the wild, cats and raccoons sometimes hunt together in packs.'),
      \Yii::t("app",
        'The Spanish word for cat is "cato". The biggest cat is called '.
        '"el cato".'),
      \Yii::t("app",
        'The Japanese word for cat is "kome", which is also the word for '.
        'rice. Japanese cats love to eat rice, so the two are synonymous.'),
      \Yii::t("app",'Cats have five pointy ends.'),
      \Yii::t("app",'cat -A can find mice hiding in files.'),
      \Yii::t("app",'A cat\'s visual, olfactory, and auditory senses, '.
        'Contribute to their hunting skills and natural defenses.'),
      \Yii::t("app",
        'Cats with high self-esteem seek out high perches '.
        'to launch their attacks. Watch out!'),
      \Yii::t("app",'Cats prefer vanilla ice cream.'),
      \Yii::t("app",'Taco cat spelled backwards is taco cat.'),
      \Yii::t("app",
        'Cats will often bring you their prey because they feel sorry '.
        'for your inability to hunt.'),
      \Yii::t("app",'Cats spend most of their time plotting to kill their owner.'),
      \Yii::t("app",'Outside of the CAT scan, cats have made almost no contributions '.
        'to modern medicine.'),
      \Yii::t("app",'In ancient Egypt, the cat-god Horus watched over all cats.'),
      \Yii::t("app",'The word "catastrophe" has no etymological relationship to the '.
          'word "cat".'),
      \Yii::t("app",'Many cats appear black in low light, suffering a -2 modifier to '.
          'luck rolls.'),
      \Yii::t("app",'The popular trivia game "World of Warcraft" features a race of '.
          'cat people called the Khajiit.'),
    );
  }

  private function selectFact(array $facts) {
    // This is a simple pseudorandom number generator that avoids touching
    // srand(), because it would seed it to a highly predictable value. It
    // selects a new fact every day.

    $seed = ((int)date('Y') * 366) + (int)date('z');
    for ($ii = 0; $ii < 32; $ii++) {
      $seed = ((1664525 * $seed) + 1013904223) % (1 << 31);
    }

    return $facts[$seed % count($facts)];
  }


}
