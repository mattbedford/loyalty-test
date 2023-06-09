<?php

class Loyalty {

  /**
   * Properties.
   * 
   * - user_id is provided to us on instantiation;
   * - points starts at 0 and can be incremented from external interaction; 
   * - date_started is set on instantiation and never changes;
   * - last_reset tracks last date customer went up or down a "loyalty level".
   */

  public $user_id;
  private $date_started;
  private $points;
  private $last_reset;

  
  function __construct($data) {
  // Constructor checks we have a user and that we don't already have a Loyalty object for them.

    if(!isset($data->id) || empty($data->id)) return;
    if(self::find($data->id) > 0 ) {
      return;
    } 
    self::save($data->id);
  
  }


 
 static function save($user_id) {
 // Static method to set our Loyalty vars and persist in the db.

    $this->user_id = $user_id;
    $this->date_started = date('Y-M-D H:i:s');
    $this->points = 0; 
    $this->last_reset = $this->date_started;
    $this->updateLoyalty();
  
  }

  static function find($user_id) {
    // Check DB for COUNT of Loyalty objects associated to this user_id. 
    // Expecting this to be 0.
    
  }

  private function getLevelFromPoints() {
  // Helper to give us the base points of user's current total (271 becomes 200)

    return floor($this->points / 100) * 100;

  }


  /**
   * Main methods of the Loyalty class.
   * 
   */

  private function resetYear() {

    // Change last reset date to today.
    $this->last_reset = date('Y-M-D H:i:s');
    $base_score_for_current_level = $this->getLevelFromPoints();

    // During reset, points are set to initial # for current level, minus 50 
    // e.g. 150 becomes 100, which becomes 50; 398 becomes 300, which becomes 250.
    $this->points = $base_score_for_current_level - 50;

    //Then save to db.
    $this->updateLoyalty();
  
  }  

  private function updateLoyalty() {
    // save instance vars to db

  }
 
  private function checkForReset() {
    // On every external interaction, first check whether $last_reset is older than 365 days (and so needs to be updated)
    // N.B. as of PHP 5.2 with DateTime objects, (<) === (less recent) 

    $latest_possible_reset = new DateTime('now - 1 year'); 
    $last_actual_reset = new DateTime($this->last_reset);
    if($last_actual_reset < $latest_possible_reset) $this->resetYear();

  }  



  /**
   *  Public functions: external systems can access an instance to:
   *  - Read current points: getPoints
   *  - Obtain percentage discount that should be applied to cart: getDiscount
   *  - Increase points of a given Loyalty instance: incrementPoints
   * 
   *  Note that at each interaction, we check the last_reset date and
   *  if necessary refresh it. 
   */

  public function getPoints() {

    $this->checkForReset();
    return $this->points;
  
  }


  public function getDiscount() {
    $this->checkForReset();
    $discount = 0;

    switch (true) {

      case $this->points <= 0;
        $discount = 0;
        break;
      case $this->points < 500;
        $discount = floor($this->points / 100) * 10;
        break;
      case $this->points >= 500;
        $discount = 50;
        break;
      default: 
        $discount = 0;
        break;
    }

    return $discount;
  }


  public function incrementPoints($points_to_add) {
    $this->checkForReset();
    $base_score_for_starting_level = $this->getLevelFromPoints();

    // Add new points to current total
    $this->points = $this->points + intval($points_to_add);

    // Check users level now after any work on points/reset has been done
    $base_score_for_maybe_new_level = $this->getLevelFromPoints();

    // Change last reset date to now only if level has changed
    if($base_score_for_starting_level !== $base_score_for_maybe_new_level) $this->last_reset = date('Y-M-D H:i:s');

    // Save
    $this->updateLoyalty();
  }

}
