/**
 * Valid getUserMedia usage — STD-TOOLCHAIN-001 MEDIA pass fixture.
 *
 * Demonstrates:
 * - sampleRate constraint set (<= 16000)
 * - No direct new MediaRecorder()
 * - Capture initiated only on user gesture (button click)
 * - No FormData full-file upload
 * - No WAV format references
 */

const startCaptureButton = document.getElementById('start-capture');

startCaptureButton?.addEventListener('click', async () => {
  const stream = await navigator.mediaDevices.getUserMedia({
    audio: {
      sampleRate: { ideal: 16000, max: 16000 },
      channelCount: { ideal: 1, max: 1 },
      echoCancellation: true,
      noiseSuppression: true,
    },
    video: false,
  });

  // Delegate to approved SDK — do not instantiate MediaRecorder directly
  const recorder = window.SparxStarSDK.createRecorder(stream, {
    mimeType: 'audio/webm;codecs=opus',
  });

  recorder.start();
});
