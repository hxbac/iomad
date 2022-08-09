// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//

/**
 * JavaScript to the recording work.
 *
 * We would like to thank the creators of atto_recordrtc, whose
 * work inspired this.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Log from 'core/log';
import ModalFactory from 'core/modal_factory';
import Notification from 'core/notification';

/**
 * Verify that the question type can work. If not, show a warning.
 *
 * @return {string} 'ok' if it looks OK, else 'nowebrtc' or 'nothttps' if there is a problem.
 */
function checkCanWork() {
    if (!(navigator.mediaDevices && window.MediaRecorder)) {
        return 'nowebrtc';
    }

    if (!(location.protocol === 'https:' || location.host.indexOf('localhost') !== -1)) {
        return 'nothttps';
    }

    return 'ok';
}

const RecorderPromise = import(M.cfg.wwwroot + '/question/type/recordrtc/js/mp3-mediarecorder@4.0.5/worker.umd.js').then(() => {
    return import(M.cfg.wwwroot + '/question/type/recordrtc/js/mp3-mediarecorder@4.0.5/index.umd.js');

}).then(recorderModule => {
    const Mp3MediaRecorder = recorderModule.Mp3MediaRecorder;

    const workerURL = URL.createObjectURL(new Blob([
        // Now load the script (UMD version) in the Workers context.
        "importScripts('" + M.cfg.wwwroot + "/question/type/recordrtc/js/mp3-mediarecorder@4.0.5/worker.umd.js');",

        // The above index.umd.js script exports all methods in a new mp3EncoderWorker object.
        "mp3EncoderWorker.initMp3MediaEncoder({vmsgWasmUrl: '" +
        M.cfg.wwwroot + "/question/type/recordrtc/js/vmsg@0.4.0/vmsg.wasm'});",
    ], {type: 'application/javascript'}));

    /**
     * Object for actually doing the recording.
     *
     * The recorder can be in one of 4 states, which is stored in a data-state
     * attribute on the button. The states are:
     *  - new:       there is no recording yet. Button shows 'Start recording'.
     *  - recording: buttons shows a countdown of remaining time. Media is being recorded.
     *  - saving:    buttons shows a progress indicator.
     *  - recorded:  button shows 'Record again'.
     *
     * @param {(AudioSettings|VideoSettings)} type
     * @param {int} timelimit
     * @param {HTMLMediaElement} mediaElement
     * @param {HTMLMediaElement} noMediaPlaceholder
     * @param {HTMLButtonElement} button
     * @param {string} filename the name of the audio or video file
     * @param {Object} owner
     * @param {Object} settings
     * @param {Object} questionDiv
     * @constructor
     */
    function Recorder(type, timelimit, mediaElement, noMediaPlaceholder,
                      button, filename, owner, settings, questionDiv) {
        /**
         * @type {Recorder} reference to this recorder, for use in event handlers.
         */
        var recorder = this;

        /**
         * @type {MediaStream} during recording, the stream of incoming media.
         */
        var mediaStream = null;

        /**
         * @type {MediaRecorder} the recorder that is capturing stream.
         */
        var mediaRecorder = null;

        /**
         * @type {Blob[]} the chunks of data that have been captured so far duing the current recording.
         */
        var chunks = [];

        /**
         * @type {number} number of bytes recorded so far, so we can auto-stop
         * before hitting Moodle's file-size limit.
         */
        var bytesRecordedSoFar = 0;

        /**
         * @type {number} time left in seconds, so we can auto-stop at the time limit.
         */
        var secondsRemaining = 0;

        /**
         * @type {number} intervalID returned by setInterval() while the timer is running.
         */
        var countdownTicker = 0;

        button.addEventListener('click', handleButtonClick);
        this.uploadMediaToServer = uploadMediaToServer; // Make this method available.

        /**
         * Handles clicks on the start/stop button.
         *
         * @param {Event} e
         */
        function handleButtonClick(e) {
            e.preventDefault();
            switch (button.dataset.state) {
                case 'new':
                case 'recorded':
                    startRecording();
                    break;
                case 'starting':
                    startSaving();
                    break;
                case 'recording':
                    stopRecording();
                    break;
            }
        }

        /**
         * Start recording (because the button was clicked).
         */
        function startRecording() {

            if (type.hidePlayerDuringRecording) {
                mediaElement.parentElement.classList.add('hide');
                noMediaPlaceholder.classList.remove('hide');
                noMediaPlaceholder.textContent = '\u00a0';
            } else {
                mediaElement.parentElement.classList.remove('hide');
                noMediaPlaceholder.classList.add('hide');
            }

            // Change look of recording button.
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-danger');

            // Disable other question buttons when current widget stared recording.
            disableAllButtons();

            // Empty the array containing the previously recorded chunks.
            chunks = [];
            bytesRecordedSoFar = 0;
            navigator.mediaDevices.getUserMedia(type.mediaConstraints)
                .then(handleCaptureStarting)
                .catch(handleCaptureFailed);
        }

        /**
         * Callback once getUserMedia has permission from the user to access the recording devices.
         *
         * @param {MediaStream} stream the stream to record.
         */
        function handleCaptureStarting(stream) {
            mediaStream = stream;

            // Setup the UI for during recording.
            mediaElement.srcObject = stream;
            mediaElement.muted = true;
            if (type.hidePlayerDuringRecording) {
                startSaving();
            } else {
                mediaElement.play();
                mediaElement.controls = false;

                button.dataset.state = 'starting';
                setButtonLabel('startrecording');
            }

            // Make button clickable again, to allow starting/stopping recording.
            button.disabled = false;
            button.focus();
        }

        /**
         * For recording types which show the media during recording,
         * this starts the loop-back display, but does not start recording it yet.
         */
        function startSaving() {
            // Initialize MediaRecorder events and start recording.
            if (type.name === 'audio') {
                mediaRecorder = new Mp3MediaRecorder(mediaStream,
                    {worker: new Worker(workerURL)});
            } else {
                mediaRecorder = new MediaRecorder(mediaStream,
                    getRecordingOptions());
            }

            mediaRecorder.ondataavailable = handleDataAvailable;
            mediaRecorder.onstop = handleRecordingHasStopped;
            mediaRecorder.start(1000); // Capture in one-second chunks. Firefox requires that.

            button.dataset.state = 'recording';
            startCountdownTimer();
        }

        /**
         * Callback that is called by the media system for each Chunk of data.
         *
         * @param {BlobEvent} event
         */
        function handleDataAvailable(event) {

            // Check there is space to store the next chunk, and if not stop.
            bytesRecordedSoFar += event.data.size;
            if (settings.maxUploadSize >= 0 && bytesRecordedSoFar >= settings.maxUploadSize) {

                // Extra check to avoid alerting twice.
                if (!localStorage.getItem('alerted')) {
                    localStorage.setItem('alerted', 'true');
                    stopRecording();
                    owner.showAlert('nearingmaxsize');

                } else {
                    localStorage.removeItem('alerted');
                }
            }

            // Store the next chunk of data.
            chunks.push(event.data);

            // Notify form-change-checker that there is now unsaved data.
            // But, don't do this in question preview where it is just annoying.
            if (typeof M.core_formchangechecker !== 'undefined' &&
                !window.location.pathname.endsWith('/question/preview.php')) {
                M.core_formchangechecker.set_form_changed();
            }
        }

        /**
         * Start recording (because the button was clicked or because we have reached a limit).
         */
        function stopRecording() {
            // Disable the button while things change.
            button.disabled = true;

            // Stop the count-down timer.
            stopCountdownTimer();

            // Update the button.
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');

            // Ask the recording to stop.
            mediaRecorder.stop();

            // Also stop each individual MediaTrack.
            var tracks = mediaStream.getTracks();
            for (var i = 0; i < tracks.length; i++) {
                tracks[i].stop();
            }
        }

        /**
         * Callback that is called by the media system once recording has finished.
         */
        function handleRecordingHasStopped() {
            if (button.dataset.state === 'new') {
                // This can happens if an error occurs when recording is starting. Do nothing.
                return;
            }

            // Set source of audio player.
            var blob = new Blob(chunks, {type: mediaRecorder.mimeType});
            mediaElement.srcObject = null;
            mediaElement.src = URL.createObjectURL(blob);

            // Show audio player with controls enabled, and unmute.
            mediaElement.muted = false;
            mediaElement.controls = true;
            mediaElement.parentElement.classList.remove('hide');
            noMediaPlaceholder.classList.add('hide');
            mediaElement.focus();

            // Encure the button while things change.
            button.disabled = true;
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
            button.dataset.state = 'recorded';

            if (chunks.length > 0) {
                owner.notifyRecordingComplete(recorder);
            }
        }

        /**
         * Function that handles errors from the recorder.
         *
         * @param {DOMException} error
         */
        function handleCaptureFailed(error) {
            Log.debug('Audio/video question: error received');
            Log.debug(error);

            setPlaceholderMessage('recordingfailed');
            setButtonLabel('recordagain');
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
            button.dataset.state = 'new';

            if (mediaRecorder) {
                mediaRecorder.stop();
            }

            // Changes 'CertainError' -> 'gumcertain' to match language string names.
            var stringName = 'gum' + error.name.replace('Error', '').toLowerCase();

            owner.showAlert(stringName);
            enableAllButtons();
        }

        /**
         * Start the countdown timer from timeLimit.
         */
        function startCountdownTimer() {
            secondsRemaining = timelimit;

            updateTimerDisplay();
            countdownTicker = setInterval(updateTimerDisplay, 1000);
        }

        /**
         * Stop the countdown timer.
         */
        function stopCountdownTimer() {
            if (countdownTicker !== 0) {
                clearInterval(countdownTicker);
                countdownTicker = 0;
            }
        }

        /**
         * Update the countdown timer, and stop recording if we have reached 0.
         */
        function updateTimerDisplay() {
            var secs = secondsRemaining % 60;
            var mins = Math.round((secondsRemaining - secs) / 60);
            setButtonLabel('recordinginprogress', pad(mins) + ':' + pad(secs));

            if (secondsRemaining === -1) {
                stopRecording();
            }
            secondsRemaining -= 1;
        }

        /**
         * Zero-pad a string to be at least two characters long.
         *
         * Used fro
         * @param {number} val, e.g. 1 or 10
         * @return {string} e.g. '01' or '10'.
         */
        function pad(val) {
            var valString = val + '';

            if (valString.length < 2) {
                return '0' + valString;
            } else {
                return valString;
            }
        }

        /**
         * Upload the recorded media back to Moodle.
         */
        function uploadMediaToServer() {
            setButtonLabel('uploadpreparing');

            var fetchRequest = new XMLHttpRequest();

            // Get media of audio/video tag.
            fetchRequest.open('GET', mediaElement.src);
            fetchRequest.responseType = 'blob';
            fetchRequest.addEventListener('load', handleRecordingFetched);
            fetchRequest.send();
        }

        /**
         * Callback called once we have the data from the media element.
         *
         * @param {ProgressEvent} e
         */
        function handleRecordingFetched(e) {
            var fetchRequest = e.target;
            if (fetchRequest.status !== 200) {
                // No data.
                return;
            }

            // Blob is now the media that the audio/video tag's src pointed to.
            var blob = fetchRequest.response;

            // Create FormData to send to PHP filepicker-upload script.
            var formData = new FormData();
            formData.append('repo_upload_file', blob, filename);
            formData.append('sesskey', M.cfg.sesskey);
            formData.append('repo_id', settings.uploadRepositoryId);
            formData.append('itemid', settings.draftItemId);
            formData.append('savepath', '/');
            formData.append('ctx_id', settings.contextId);
            formData.append('overwrite', 1);

            var uploadRequest = new XMLHttpRequest();
            uploadRequest.addEventListener('readystatechange', handleUploadReadyStateChanged);
            uploadRequest.upload.addEventListener('progress', handleUploadProgress);
            uploadRequest.addEventListener('error', handleUploadError);
            uploadRequest.addEventListener('abort', handleUploadAbort);
            uploadRequest.open('POST', M.cfg.wwwroot + '/repository/repository_ajax.php?action=upload');
            uploadRequest.send(formData);
        }

        /**
         * Callback for when the upload completes.
         * @param {ProgressEvent} e
         */
        function handleUploadReadyStateChanged(e) {
            var uploadRequest = e.target;
            if (uploadRequest.readyState === 4 && uploadRequest.status === 200) {
                // When request finished and successful.
                setButtonLabel('recordagain');
                enableAllButtons();
            } else if (uploadRequest.status === 404) {
                setPlaceholderMessage('uploadfailed404');
                enableAllButtons();
            }
        }

        /**
         * Callback for updating the upload progress.
         * @param {ProgressEvent} e
         */
        function handleUploadProgress(e) {
            setButtonLabel('uploadprogress', Math.round(e.loaded / e.total * 100) + '%');
        }

        /**
         * Callback for when the upload fails with an error.
         */
        function handleUploadError() {
            setPlaceholderMessage('uploadfailed');
            enableAllButtons();
        }

        /**
         * Callback for when the upload fails with an error.
         */
        function handleUploadAbort() {
            setPlaceholderMessage('uploadaborted');
            enableAllButtons();
        }

        /**
         * Display a progress message in the upload progress area.
         *
         * @param {string} langString
         * @param {Object|String} a optional variable to populate placeholder with
         */
        function setButtonLabel(langString, a) {
            button.innerText = M.util.get_string(langString, 'qtype_recordrtc', a);
        }

        /**
         * Display a message in the upload progress area.
         *
         * @param {string} langString
         * @param {Object|String} a optional variable to populate placeholder with
         */
        function setPlaceholderMessage(langString, a) {
            noMediaPlaceholder.textContent = M.util.get_string(langString, 'qtype_recordrtc', a);
            mediaElement.parentElement.classList.add('hide');
            noMediaPlaceholder.classList.remove('hide');
        }

        /**
         * Select best options for the recording codec.
         *
         * @returns {Object}
         */
        function getRecordingOptions() {
            var options = {};

            // Get the relevant bit rates from settings.
            if (type.name === 'audio') {
                options.audioBitsPerSecond = parseInt(settings.audioBitRate, 10);
            } else if (type.name === 'video') {
                options.videoBitsPerSecond = parseInt(settings.videoBitRate, 10);
                options.videoWidth = parseInt(settings.videoWidth, 10);
                options.videoHeight = parseInt(settings.videoHeight, 10);

                // Go through our list of mimeTypes, and take the first one that will work.
                for (var i = 0; i < type.mimeTypes.length; i++) {
                    if (MediaRecorder.isTypeSupported(type.mimeTypes[i])) {
                        options.mimeType = type.mimeTypes[i];
                        break;
                    }
                }
            }

            return options;
        }

        /**
         * Enable all buttons in the question.
         */
        function enableAllButtons() {
            disableOrEnableButtons(true);
            owner.notifyButtonStatesChanged();
        }

        /**
         * Disable all buttons in the question.
         */
        function disableAllButtons() {
            disableOrEnableButtons(false);
        }

        /**
         * Disables/enables other question buttons when current widget started recording/finished recording.
         *
         * @param {boolean} enabled true if the button should be enabled.
         */
        function disableOrEnableButtons(enabled = false) {
            questionDiv.querySelectorAll('button, input[type=submit], input[type=button]').forEach(
                function(button) {
                    button.disabled = !enabled;
                }
            );
        }
    }

    return Recorder;
});

/**
 * Object that controls the settings for recording audio.
 *
 * @constructor
 */
function AudioSettings() {
    this.name = 'audio';
    this.hidePlayerDuringRecording = true;
    this.mediaConstraints = {
        audio: true
    };
    this.mimeTypes = [
        'audio/mpeg',
    ];
}

/**
 * Object that controls the settings for recording video.
 *
 * @param {number} width desired width.
 * @param {number} height desired height.
 * @constructor
 */
function VideoSettings(width, height) {
    this.name = 'video';
    this.hidePlayerDuringRecording = false;
    this.mediaConstraints = {
        audio: true,
        video: {
            width: {ideal: width},
            height: {ideal: height}
        }
    };
    this.mimeTypes = [
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=h264,opus',
        'video/webm;codecs=vp8,opus'
    ];
}

/**
 * Represents one record audio or video question.
 *
 * @param {string} questionId id of the outer question div.
 * @param {Object} settings like audio bit rate.
 * @constructor
 */
function RecordRtcQuestion(questionId, settings) {
    var questionDiv = document.getElementById(questionId);

    // Check if the RTC API can work here.
    var result = checkCanWork();
    if (result === 'nothttps') {
        questionDiv.querySelector('.https-warning').classList.remove('hide');
        return;
    } else if (result === 'nowebrtc') {
        questionDiv.querySelector('.no-webrtc-warning').classList.remove('hide');
        return;
    }

    // Make the callback functions available.
    this.showAlert = showAlert;
    this.notifyRecordingComplete = notifyRecordingComplete;
    this.notifyButtonStatesChanged = setSubmitButtonState;
    const thisQuestion = this;

    // We may have more than one widget in a question.
    questionDiv.querySelectorAll('.audio-widget, .video-widget').forEach(function(widget) {
        // Get the key UI elements.
        var type = widget.dataset.mediaType;
        var timelimit = widget.dataset.maxRecordingDuration;
        var button = widget.querySelector('.record-button button');
        var mediaElement = widget.querySelector('.media-player ' + type);
        var noMediaPlaceholder = widget.querySelector('.no-recording-placeholder');
        var filename = widget.dataset.recordingFilename;

        // Get the appropriate options.
        var typeInfo;
        if (type === 'audio') {
            typeInfo = new AudioSettings();
        } else {
            typeInfo = new VideoSettings(settings.videoWidth, settings.videoHeight);
        }

        // Create the recorder.
        RecorderPromise.then(Recorder => {
            new Recorder(typeInfo, timelimit, mediaElement, noMediaPlaceholder, button,
                filename, thisQuestion, settings, questionDiv);
            return 'Why should I have to return anything here?';
        }).catch(Notification.exception);
    });
    setSubmitButtonState();

    /**
     * Set the state of the question's submit button.
     *
     * If any recorder does not yet have a recording, then disable the button.
     * Otherwise, enable it.
     */
    function setSubmitButtonState() {
        var anyRecorded = false;
        questionDiv.querySelectorAll('.audio-widget, .video-widget').forEach(function(widget) {
            if (widget.querySelector('.record-button button').dataset.state === 'recorded') {
                anyRecorded = true;
            }
        });
        var submitButton = questionDiv.querySelector('input.submit[type=submit]');
        if (submitButton) {
            submitButton.disabled = !anyRecorded;
       }
    }

    /**
     * Show a modal alert.
     *
     * @param {string} subject Subject is the content of the alert (which error the alert is for).
     * @return {Promise}
     */
    function showAlert(subject) {
        return ModalFactory.create({
            type: ModalFactory.types.ALERT,
            title: M.util.get_string(subject + '_title', 'qtype_recordrtc'),
            body: M.util.get_string(subject, 'qtype_recordrtc'),
        }).then(function(modal) {
            modal.show();
            return modal;
        });
    }

    /**
     * Callback called when the recording is completed.
     *
     * @param {Recorder} recorder the recorder.
     */
    function notifyRecordingComplete(recorder) {
        recorder.uploadMediaToServer();
    }
}

/**
 * Initialise a record audio or video question.
 *
 * @param {string} questionId id of the outer question div.
 * @param {Object} settings like audio bit rate.
 */
function init(questionId, settings) {
    M.util.js_pending('init-' + questionId);
    new RecordRtcQuestion(questionId, settings);
    M.util.js_complete('init-' + questionId);
}

export {
    init
};
