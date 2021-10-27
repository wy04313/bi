new p5();
window.onload = function () {
  //functions definition

  //class definition
  class segm {
    constructor(x, y, l, b) {
      this.b = Math.random() * b * 2;
      this.x0 = x;
      this.y0 = y;
      this.a = Math.random() * 2 * Math.PI;
      this.x1 = this.x0 + l * Math.cos(this.a);
      this.y1 = this.y0 + l * Math.sin(this.a);
      this.l = l;
    }
    update(x, y) {
      this.x0 = x;
      this.y0 = y;
      this.a = Math.atan2(this.y1 - this.y0, this.x1 - this.x0);
      this.x1 = this.x0 + this.l * Math.cos(this.a);
      this.y1 = this.y0 + this.l * Math.sin(this.a);
    }
  }
  class rope {
    constructor(tx, ty, l, b, slq, typ) {
      this.b = b;
      if (typ == "l") {
        this.res = l / 4;
      } else {
        this.res = l / slq;
      }
      this.type = typ;
      this.l = l;
      this.segm = [];
      this.segm.push(new segm(tx, ty, this.l / this.res, this.b));
      for (let i = 1; i < this.res; i++) {
        this.segm.push(
          new segm(
            this.segm[i - 1].x1,
            this.segm[i - 1].y1,
            this.l / this.res,
            this.b
          )
        );
      }
    }
    update(t) {
      this.segm[0].update(t.x, t.y);
      for (let i = 1; i < this.res; i++) {
        this.segm[i].update(this.segm[i - 1].x1, this.segm[i - 1].y1);
      }
    }
    show() {
      if (this.type == "l") {
        c.beginPath();
        for (let i = 0; i < this.segm.length; i++) {
          c.lineTo(this.segm[i].x0, this.segm[i].y0);
        }
        c.lineTo(
          this.segm[this.segm.length - 1].x1,
          this.segm[this.segm.length - 1].y1
        );
        c.strokeStyle = "white";
        c.lineWidth = this.b;
        c.stroke();

        // c.beginPath();
        // c.arc(this.segm[0].x0, this.segm[0].y0, 1, 0, 2 * Math.PI);
        // c.fillStyle = "white";
        // c.fill();

        // c.beginPath();
        // c.arc(
        //   this.segm[this.segm.length - 1].x1,
        //   this.segm[this.segm.length - 1].y1,
        //   2,
        //   0,
        //   2 * Math.PI
        // );
        // c.fillStyle = "white";
        // c.fill();
      } else {
        for (let i = 0; i < this.segm.length; i++) {
          c.beginPath();
          c.arc(
            this.segm[i].x0,
            this.segm[i].y0,
            this.segm[i].b,
            0,
            2 * Math.PI
          );
          c.fillStyle = "white";
          c.fill();
        }
        // c.beginPath();
        // c.arc(
        //   this.segm[this.segm.length - 1].x1,
        //   this.segm[this.segm.length - 1].y1,
        //   2,
        //   0,
        //   2 * Math.PI
        // );
        // c.fillStyle = "white";
        // c.fill();
      }
    }
  }

  //setting up canvas
  let c = init("canvas").c,
    canvas = init("canvas").canvas,
    w = (canvas.width = window.innerWidth),
    h = (canvas.height = window.innerHeight),
    ropes = [];

  //variables definition
  let nameOfVariable = "value",
    mouse = {},
    last_mouse = {},
    rl = 50,
    randl = [],
    target = { x: w / 2, y: h / 2 },
    last_target = {},
    t = [],
    q = 10,
    da = [],
    type = "l",
    ang = [],
    la = [],
    time = 0,
    noisescl = (45 * Math.PI) / 180;

  for (let i = 0; i < 500; i++) {
    if (Math.random() > 0.75) {
      type = "l";
    } else {
      type = "o";
    }
    ropes.push(
      new rope(
        Math.random() * w,
        Math.random() * h,
        (Math.random() * 1 + 0.5) * 100,
        0.5,
        10,
        type
      )
    );
    randl.push(Math.random() * 2 - 1);
    da.push(0);
    t.push({ x: ropes[i].segm[0].x0, y: ropes[i].segm[0].y0 });
    ang.push(0);
    la.push(0);
  }

  //place for objects in animation
  function draw() {
    time++;
    for (let i = 0; i < ropes.length; i++) {
      ang[i] =
        la[i] + (noise(time / 100, (10000 * i) / 1) * noisescl - noisescl / 2);

      t[i].x += Math.cos(ang[i]) * 2;
      t[i].y += Math.sin(ang[i]) * 2;

      if (randl[i] > 0) {
        da[i] += (1 - randl[i]) / 10;
      } else {
        da[i] += (-1 - randl[i]) / 10;
      }
      ropes[i].update(t[i]);
      ropes[i].show();
      la[i] = ang[i];
    }
  }

  //mouse position
  canvas.addEventListener(
    "mousemove",
    function (e) {
      last_mouse.x = mouse.x;
      last_mouse.y = mouse.y;

      mouse.x = e.pageX - this.offsetLeft;
      mouse.y = e.pageY - this.offsetTop;
    },
    false
  );

  canvas.addEventListener("mouseleave", function (e) {
    mouse.x = false;
    mouse.y = false;
  });

  //animation frame
  function loop() {
    window.requestAnimFrame(loop);
    // c.clearRect(0, 0, w, h);
    c.globalCompositeOperation = "multiply";
    c.fillStyle = "rgba(1,12,69,0.05)";
    c.fillRect(0, 0, w, h);
    c.globalCompositeOperation = "source-over";
    draw();
  }

  //window resize
  window.addEventListener("resize", function () {
    (w = canvas.width = window.innerWidth),
      (h = canvas.height = window.innerHeight);
    loop();
  });

  //animation runner
  loop();
  setInterval(loop, 1000 / 60);
};
