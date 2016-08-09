/**
 * Provices a mechanism for executing actions on the sever while providing controls and status to the user
 * The api being called must respond with a "status", "stage", and "progress".
 * @param apiUrl
 * @constructor
 */
function Process(apiUrl) {
  var self = this;
  self.apiUrl = apiUrl; // this is the url that will be ran until the process is complete
  self.onCancel = function(){}; // callback to execute if the process is canceled
  self.onStart = function(){}; // callback to execute when the process starts
  self.onPause = function(stage){}; // callback to execute when the process is paused
  self.onResume = function(){}; // callback to execute when the process is resumed
  self.onProgress = function(progress){}; // callback to execute when progress is made
  self.onComplete = function(){}; // callback to execute when the process is complete
  self.onError = function(response){}; // callbackt to execute when the process fails
  self.state = {}; // contains information about the process state

  /**
   * Peforms the process execution
   * @param {} stage the stage of execution
   */
  function execute(stage) {
    $.get(self.apiUrl, {'stage':stage}, function(response) {
      if(response.status == 'ok') {
        console.debug(response);
        self.onProgress(response.progress);

        if(response.progress >= 1) {
          self.state.completed = true;
          self.state.running = false;
          self.onComplete();
          return;
        }

        if (self.state.paused) {
          self.state.stage = response.stage;
          self.onPause(response.stage);
          return;
        }

        if (self.state.aborted) {
          self.state.stage = {};
          self.onCancel();
          return;
        }

        execute(response.stage);
      } else {
        self.state.running = false;
        self.paused = false;
        self.aborted = false;
        self.state.stage = {};
        self.onError(response);
      }
    });
  }

  /**
   * Begins executing the process
   */
  self.start = function() {
    if(self.state.running) return;

    // reset the state
    self.state = {
      paused:false,
      aborted:false,
      completed:false,
      running:true,
      stage:{} // the stage of execution
    };

    self.onStart();
    execute(self.state.stage);
  }

  /**
   * Flags the process to stop after the current execution.
   */
  self.stop = function() {
    if(self.state.aborted) return;
    self.state.aborted = true;
    self.state.running = false;
  }

  /**
   * Flags the process to pause after the current execution
   */
  self.pause = function() {
    if(self.state.paused) return;
    self.state.paused = true;
  }

  /**
   * Resumes the process after being paused
   */
  self.resume = function() {
    if(!self.state.paused) return;
    if(Object.keys(self.state.stage).length) {
      self.state.paused = false;
      self.onResume();
      execute(self.state.stage);
    } else {
      console.debug('the stage is empty. a resume cannot be performed.');
    }
  }
}