(() => {
  const app = document.getElementById("app");
  if (!app) return;

  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const faMap = "۰۱۲۳۴۵۶۷۸۹";
  const fa = (s) => String(s).replace(/\d/g, (d) => faMap[d]);
  const en = (s) => String(s)
    .replace(/[۰-۹]/g, (d) => "0123456789"["۰۱۲۳۴۵۶۷۸۹".indexOf(d)])
    .replace(/[٠-٩]/g, (d) => "0123456789"["٠١٢٣٤٥٦٧٨٩".indexOf(d)]);

  const els = {
    lamp: document.getElementById("lamp"),
    copyId: document.getElementById("copy-id"),
    copyHint: document.getElementById("copy-hint"),
    dial: document.getElementById("dial-form"),
    peerInput: document.getElementById("peer-id"),
    threads: document.getElementById("threads"),
    stage: document.getElementById("stage"),
    empty: document.getElementById("empty"),
    peerName: document.getElementById("peer-name"),
    peerMeta: document.getElementById("peer-meta"),
    log: document.getElementById("log"),
    composer: document.getElementById("composer"),
    msg: document.getElementById("msg"),
    videos: document.getElementById("videos"),
    localVideo: document.getElementById("local-video"),
    remoteVideo: document.getElementById("remote-video"),
    callStatus: document.getElementById("call-status"),
    hangup: document.getElementById("btn-hangup"),
    audio: document.getElementById("btn-audio"),
    video: document.getElementById("btn-video"),
    incoming: document.getElementById("incoming"),
    incomingName: document.getElementById("incoming-name"),
    incomingMedia: document.getElementById("incoming-media"),
    accept: document.getElementById("btn-accept"),
    reject: document.getElementById("btn-reject"),
  };

  const state = {
    conversationId: 0,
    peer: null,
    sinceMsg: 0,
    sinceSig: 0,
    seenMsg: new Set(),
    call: null,
    serverCallId: 0,
    pc: null,
    localStream: null,
    pendingIce: [],
    seenIncoming: 0,
  };

  const iceServers = [
    { urls: "stun:stun.cloudflare.com:3478" },
    { urls: "stun:stun.l.google.com:19302" },
    { urls: "stun:stun1.l.google.com:19302" },
  ];

  async function api(path, body) {
    const res = await fetch(path, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": csrf,
      },
      body: JSON.stringify(body),
    });
    const json = await res.json().catch(() => ({ ok: false, error: "پاسخ نامعتبر" }));
    if (!json.ok) throw new Error(json.error || "خطا");
    return json;
  }

  function secureOk() {
    return window.isSecureContext;
  }

  els.copyId.addEventListener("click", async () => {
    const id = app.dataset.publicId;
    try {
      await navigator.clipboard.writeText(id);
      els.copyHint.textContent = "شناسه کپی شد.";
    } catch {
      els.copyHint.textContent = "شناسه: " + fa(id);
    }
  });

  els.dial.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const json = await api("api/start.php", { public_id: en(els.peerInput.value.trim()) });
      openThread(json.conversation_id, json.peer);
    } catch (err) {
      els.copyHint.textContent = err.message;
    }
  });

  function openThread(id, peer) {
    state.conversationId = id;
    state.peer = peer;
    state.sinceMsg = 0;
    state.seenMsg = new Set();
    els.log.innerHTML = "";
    els.empty.hidden = true;
    els.stage.hidden = false;
    els.peerName.textContent = peer.username;
    renderPeerMeta();
    highlightThread();
  }

  function renderPeerMeta() {
    if (!state.peer) return;
    const live = state.peer.online ? "آنلاین" : "آفلاین";
    els.peerMeta.innerHTML = `<span class="dot ${state.peer.online ? "live" : ""}"></span>${live} · شناسه ${fa(state.peer.public_id)}`;
  }

  function highlightThread() {
    els.threads.querySelectorAll(".thread").forEach((btn) => {
      btn.classList.toggle("active", Number(btn.dataset.id) === state.conversationId);
    });
  }

  function renderThreads(list) {
    els.threads.innerHTML = "";
    list.forEach((item) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "thread";
      btn.dataset.id = String(item.id);
      btn.innerHTML = `<strong><span class="dot ${item.peer.online ? "live" : ""}"></span>${escapeHtml(item.peer.username)}</strong><span>${escapeHtml(item.preview || "گفتگوی تازه")}</span>`;
      btn.addEventListener("click", () => openThread(item.id, item.peer));
      els.threads.appendChild(btn);
    });
    highlightThread();
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
    }[c]));
  }

  function addMessage(m) {
    if (state.seenMsg.has(m.id)) return;
    state.seenMsg.add(m.id);
    const div = document.createElement("div");
    div.className = "bubble" + (m.mine ? " mine" : "");
    div.textContent = m.body;
    els.log.appendChild(div);
    els.log.scrollTop = els.log.scrollHeight;
    if (m.id > state.sinceMsg) state.sinceMsg = m.id;
  }

  els.composer.addEventListener("submit", async (e) => {
    e.preventDefault();
    const body = els.msg.value.trim();
    if (!body || !state.conversationId) return;
    els.msg.value = "";
    try {
      const json = await api("api/message.php", { conversation_id: state.conversationId, body });
      addMessage(json.message);
    } catch (err) {
      els.msg.value = body;
      els.peerMeta.textContent = err.message;
    }
  });

  els.audio.addEventListener("click", () => startCall("audio"));
  els.video.addEventListener("click", () => startCall("video"));
  els.hangup.addEventListener("click", hangup);
  els.accept.addEventListener("click", acceptCall);
  els.reject.addEventListener("click", rejectCall);

  async function startCall(media) {
    if (!state.peer) return;
    if (!secureOk()) {
      alert("برای دوربین و میکروفون باید سایت با HTTPS باز شود (یا روی localhost).");
      return;
    }
    try {
      const json = await api("api/call.php", { action: "start", public_id: state.peer.public_id, media });
      state.call = json.call;
      await prepareMedia(media);
      showVideos(media);
      els.lamp.classList.add("on");
      els.callStatus.textContent = "در حال زنگ خوردن…";
    } catch (err) {
      alert(err.message);
    }
  }

  async function acceptCall() {
    if (!state.call && incomingCache) {
      state.call = incomingCache;
    }
    if (!state.call) return;
    try {
      const json = await api("api/call.php", { action: "accept", call_id: state.call.id });
      state.call = { ...state.call, ...json.call };
      els.incoming.hidden = true;
      await prepareMedia(state.call.media);
      showVideos(state.call.media);
      els.lamp.classList.add("on");
      els.callStatus.textContent = "در حال اتصال…";
    } catch (err) {
      alert(err.message);
    }
  }

  async function rejectCall() {
    const id = (state.call && state.call.id) || (incomingCache && incomingCache.id);
    if (!id) return;
    try {
      await api("api/call.php", { action: "reject", call_id: id });
    } catch (_) { /* ignore */ }
    els.incoming.hidden = true;
    incomingCache = null;
  }

  async function hangup() {
    if (state.call) {
      try {
        await api("api/call.php", { action: "hangup", call_id: state.call.id });
      } catch (_) { /* ignore */ }
    }
    teardownCall();
  }

  let incomingCache = null;

  function showVideos(media) {
    els.videos.hidden = false;
    els.videos.classList.toggle("is-video", media === "video");
    document.body.classList.toggle("in-call", media === "video");
  }

  function hideVideos() {
    els.videos.hidden = true;
    els.videos.classList.remove("is-video");
    document.body.classList.remove("in-call");
  }

  function teardownCall() {
    if (state.pc) {
      state.pc.onicecandidate = null;
      state.pc.ontrack = null;
      state.pc.close();
    }
    if (state.localStream) {
      state.localStream.getTracks().forEach((t) => t.stop());
    }
    state.pc = null;
    state.localStream = null;
    state.call = null;
    state.serverCallId = 0;
    state.makingOffer = false;
    state.pendingIce = [];
    state.sinceSig = 0;
    els.localVideo.srcObject = null;
    els.remoteVideo.srcObject = null;
    hideVideos();
    els.lamp.classList.remove("on");
    els.callStatus.textContent = "";
    els.incoming.hidden = true;
  }

  let mediaLock = null;

  async function prepareMedia(media) {
    if (mediaLock) {
      await mediaLock;
      return;
    }
    mediaLock = (async () => {
      if (!state.localStream) {
        const constraints = {
          audio: true,
          video: media === "video"
            ? { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 720 } }
            : false,
        };
        state.localStream = await navigator.mediaDevices.getUserMedia(constraints);
      }
      els.localVideo.srcObject = state.localStream;
      els.localVideo.hidden = media !== "video";
      ensurePc();
      const hasSender = state.pc.getSenders().some((s) => s.track);
      if (!hasSender) {
        state.localStream.getTracks().forEach((t) => state.pc.addTrack(t, state.localStream));
      }
    })();
    try {
      await mediaLock;
    } finally {
      mediaLock = null;
    }
  }

  function ensurePc() {
    if (state.pc) return;
    const pc = new RTCPeerConnection({ iceServers });
    pc.onicecandidate = (ev) => {
      if (!ev.candidate || !state.call) return;
      api("api/signal.php", {
        call_id: state.call.id,
        payload: { type: "ice", candidate: ev.candidate },
      }).catch(() => {});
    };
    pc.ontrack = (ev) => {
      const stream = ev.streams[0] || new MediaStream([ev.track]);
      els.remoteVideo.srcObject = stream;
      if (ev.track && ev.track.kind === "video") {
        els.videos.classList.add("is-video");
      }
      els.callStatus.textContent = "متصل شدید";
    };
    pc.onconnectionstatechange = () => {
      if (["failed", "disconnected", "closed"].includes(pc.connectionState) && state.call) {
        els.callStatus.textContent = pc.connectionState === "failed"
          ? "اتصال برقرار نشد. هر دو باید در شبکهٔ باز باشید."
          : "ارتباط قطع شد";
      }
    };
    state.pc = pc;
  }

  async function maybeOffer() {
    if (!state.call || state.call.role !== "caller") return;
    if (state.call.status !== "active") return;
    if (!state.pc || state.makingOffer) return;
    if (state.pc.localDescription) return;
    state.makingOffer = true;
    const offer = await state.pc.createOffer();
    await state.pc.setLocalDescription(offer);
    await api("api/signal.php", {
      call_id: state.call.id,
      payload: { type: "offer", sdp: state.pc.localDescription },
    });
  }

  async function flushIce() {
    if (!state.pc || !state.pc.remoteDescription) return;
    const queued = state.pendingIce.splice(0);
    for (const c of queued) {
      try {
        await state.pc.addIceCandidate(c);
      } catch (_) { /* stale candidate */ }
    }
  }

  async function handleSignal(item) {
    const p = item.payload;
    ensurePc();
    if (p.type === "offer" && p.sdp) {
      await state.pc.setRemoteDescription(p.sdp);
      if (!state.localStream && state.call) {
        await prepareMedia(state.call.media);
      }
      const answer = await state.pc.createAnswer();
      await state.pc.setLocalDescription(answer);
      await flushIce();
      await api("api/signal.php", {
        call_id: state.call.id,
        payload: { type: "answer", sdp: state.pc.localDescription },
      });
    } else if (p.type === "answer" && p.sdp) {
      if (!state.pc.currentRemoteDescription) {
        await state.pc.setRemoteDescription(p.sdp);
        await flushIce();
      }
    } else if (p.type === "ice" && p.candidate) {
      if (state.pc.remoteDescription) {
        try {
          await state.pc.addIceCandidate(p.candidate);
        } catch (_) { /* ignore */ }
      } else {
        state.pendingIce.push(p.candidate);
      }
    }
    if (item.id > state.sinceSig) state.sinceSig = item.id;
  }

  async function poll() {
    const q = new URLSearchParams({
      since_msg: String(state.sinceMsg),
      since_sig: String(state.sinceSig),
      conversation_id: String(state.conversationId || 0),
    });
    try {
      const res = await fetch("api/poll.php?" + q.toString(), { cache: "no-store" });
      const json = await res.json();
      if (!json.ok) return;
      renderThreads(json.conversations || []);
      if (json.peer) {
        state.peer = json.peer;
        renderPeerMeta();
      }
      (json.messages || []).forEach(addMessage);

      if (json.incoming && json.incoming.id !== state.seenIncoming) {
        incomingCache = json.incoming;
        state.seenIncoming = json.incoming.id;
        els.incoming.hidden = false;
        els.incomingName.textContent = json.incoming.peer.username;
        els.incomingMedia.textContent = json.incoming.media === "audio" ? "تماس صوتی" : "تماس تصویری";
        if (json.incoming.conversation_id) {
          openThread(json.incoming.conversation_id, json.incoming.peer);
        }
      }

      if (json.call) {
        state.serverCallId = json.call.id;
        const prev = state.call && state.call.status;
        state.call = { ...(state.call || {}), ...json.call };
        if (json.call.status === "active" && prev !== "active") {
          els.callStatus.textContent = "در حال اتصال…";
          showVideos(json.call.media);
          maybeOffer().catch((e) => { els.callStatus.textContent = e.message; });
        }
        if (["ended", "rejected", "missed"].includes(json.call.status)) {
          teardownCall();
        }
      } else if (state.serverCallId) {
        teardownCall();
      }

      for (const sig of json.signals || []) {
        await handleSignal(sig);
      }
    } catch (_) { /* transient */ }
  }

  if (!secureOk()) {
    const note = document.createElement("p");
    note.className = "insecure";
    note.textContent = "این صفحه HTTPS نیست. مرورگر اجازهٔ میکروفون و دوربین را روی هاست نمی‌دهد تا وقتی گواهی SSL فعال شود.";
    document.querySelector(".rail").appendChild(note);
  }

  poll();
  setInterval(poll, 1200);
})();
