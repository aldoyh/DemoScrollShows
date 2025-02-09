<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Whirl Demo</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: hsl(0 0% 0%);
      overflow: hidden;
    }

    .whirl {
      --size: 80vmin;
      width: var(--size);
      height: var(--size);
      position: relative;
      transform-style: preserve-3d;
      perspective: 500px;
      /* Add animation for idle movement */
      animation: idleRotate 20s linear infinite;
      transition: transform 0.5s ease; /* Smooth transition for presentation mode */
    }

    .whirl__piece {
      --index: 0;
      --rgb: 0 0 0;
      position: absolute;
      aspect-ratio: 1;
      width: calc(var(--size) * 0.5);
      transform-origin: 50% 100%;
      transform: rotateZ(calc(var(--index) * (360deg / var(--piece-count)))) translateY(-50%);
      filter: brightness(calc((var(--index) + 1) / var(--piece-count) * 3));
      transition: transform 0.8s ease, opacity 0.8s ease; /* Smooth transitions */
      opacity: 1;
    }

    .whirl__piece img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 8px;
      opacity: 0.8;
    }

    .whirl__piece:after {
      content: "";
      position: absolute;
      inset: 0;
      background: hsl(var(--rgb) / 0.25);
      mix-blend-mode: color;
    }
    .whirl__piece.hidden {
      opacity: 0;
      /*  transform: scale(0.5);  Removed scale from hidden */
      pointer-events: none; /* Prevent interaction with hidden pieces */
    }

    /* Keyframes for idle rotation */
    @keyframes idleRotate {
      from {
        transform: rotateY(0deg) rotateX(0deg);
      }
      to {
        transform: rotateY(360deg) rotateX(360deg);
      }
    }
    /* Presentation mode styles */
    .whirl.presentation-mode {
      animation-play-state: paused; /* Pause the idle animation */
    }
  </style>
</head>
<body>
  <div class="whirl"></div>

  <script>
    {{IMAGES}}
    // Ensure IMAGES is initialized before use
    const IMAGES = IMAGES_DATA;

    const PIECE_COUNT = 8;
    const SNAP = 0.05;

    const clamp = (num, min, max) => Math.min(Math.max(num, min), max);
    const lerp = (start, end, amt) => {
      return (1 - amt) * start + amt * end;
    };

    class Whirl {
      constructor() {
        this.root = document.querySelector('.whirl');
        this.position = { x: 0.5, y: 0.5 };
        this.pieces = [];
        this.bounds = this.root.getBoundingClientRect();
        this.rotations = Array(PIECE_COUNT).fill(0);
        this.setup();
        this.listen();
        this.update();
        this.presentationIndex = 0;
        this.presentationMode = false;
        this.presentationInterval = null;
      }


      setup() {
        document.documentElement.style.setProperty('--piece-count', PIECE_COUNT);
        for (let p = 0; p < PIECE_COUNT; p++) {
          const piece = document.createElement('div');
          piece.className = 'whirl__piece';
          const img = document.createElement('img');
          img.src = IMAGES[p];
          piece.appendChild(img);
          piece.style.setProperty('--rgb', `${Math.floor(Math.random() * 255)} ${Math.floor(Math.random() * 255)} ${Math.floor(Math.random() * 255)}`);
          piece.style.setProperty('--index', p);
          this.pieces.push(piece);
          this.root.appendChild(piece);
        }
      }

      listen() {
        window.addEventListener('pointermove', this.track.bind(this));
        window.addEventListener('pointerdown', this.track.bind(this));
        window.addEventListener('resize', this.setBounds.bind(this));
        window.addEventListener('mouseout', this.handleMouseOut.bind(this)); // Corrected event name
      }

        handleMouseOut(event) {
            // Check if the mouse is leaving the entire document (to outside the window)
            if (!event.relatedTarget && !event.toElement) {  //more reliable
                if (!this.presentationMode) {
                  this.startPresentation();
                }
            }
        }


      setBounds() {
        this.bounds = this.root.getBoundingClientRect();
      }

      track(e) {
        const x = (e.clientX - this.bounds.left) / this.bounds.width;
        const y = (e.clientY - this.bounds.top) / this.bounds.height;
        this.position = { x, y };
      }

      update() {
        if (!this.presentationMode) {
          this.pieces.forEach((piece, index) => {
            const { x, y } = this.position;
            const pieceRotation = (index * (360 / PIECE_COUNT));
            const rotation = pieceRotation + ((x * 360) + (y * 180));
            this.rotations[index] = lerp(this.rotations[index], rotation, SNAP);
            piece.style.transform = `rotateZ(${this.rotations[index]}deg) translateY(-50%) rotateX(${y * 30}deg) rotateY(${x * 30}deg)`;
          });
        }
        requestAnimationFrame(this.update.bind(this));
      }


        startPresentation() {
            if (this.presentationMode) return; // Prevent restarting

            this.presentationMode = true;
            this.root.classList.add('presentation-mode');
            this.presentationIndex = 0;
              // Set initial positions *before* starting the interval
            this.presentSlide();
            this.presentationInterval = setInterval(() => {

                this.presentSlide();

            }, 2000);
        }


        presentSlide() {

            this.pieces.forEach((piece, index) => {
                if (index === this.presentationIndex) {
                    piece.classList.remove('hidden');
                    // Center the piece using top/left and transform: translate.
                    piece.style.left = '50%';
                    piece.style.top = '50%';
                    piece.style.transform = 'translate(-50%, -50%) scale(1.2)'; // Center and scale
                    piece.style.zIndex = 10; // Ensure the current slide is on top

                } else {
                    piece.classList.add('hidden');
                     piece.style.zIndex = 'auto'; // Reset z-index for hidden pieces
                }
            });

             this.presentationIndex = (this.presentationIndex + 1) % PIECE_COUNT;
              if (this.presentationIndex === 0) {
                this.endPresentation();
              }

        }


        endPresentation() {
            clearInterval(this.presentationInterval);
            this.presentationMode = false;
            this.root.classList.remove('presentation-mode');

             this.pieces.forEach((piece) => {
                piece.classList.remove('hidden');
                piece.style.left = '';       // Remove inline styles
                piece.style.top = '';
                piece.style.zIndex = '';      // Remove z-index
                //transform will be reset in the update()
              });
        }
    }

    new Whirl();

  </script>
</body>
</html>