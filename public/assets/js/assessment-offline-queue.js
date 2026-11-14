(() => {
  const form = document.querySelector("form[data-assessment-leave-guard], form[data-assessment-answer-autosave]");

  if (!form) {
    return;
  }

  const assessmentId = String(form.dataset.assessmentId || "");
  const userId = String(form.dataset.userId || "");
  const attemptKey = String(form.dataset.attemptKey || "0");
  const storageKey = "assessmentOffline.v1." + assessmentId + "." + userId + "." + attemptKey;
  const ASSET_PREFIX = "offline-asset:";
  const DB_NAME = "assessment-offline";
  const STORE = "blobs";

  if (!assessmentId || !userId) {
    return;
  }

  let offline = typeof navigator.onLine === "boolean" ? !navigator.onLine : false;
  let flushing = false;
  const blobUrls = new Map();

  const emptyState = () => ({
    answers: {},
    pendingSubmit: false,
    timedOut: false,
    updatedAt: 0,
  });

  const readState = () => {
    try {
      const parsed = JSON.parse(localStorage.getItem(storageKey) || "null");

      if (!parsed || typeof parsed !== "object") {
        return emptyState();
      }

      return {
        answers: parsed.answers && typeof parsed.answers === "object" ? parsed.answers : {},
        pendingSubmit: Boolean(parsed.pendingSubmit),
        timedOut: Boolean(parsed.timedOut),
        updatedAt: Number(parsed.updatedAt || 0),
      };
    } catch (_error) {
      return emptyState();
    }
  };

  const writeState = (state) => {
    state.updatedAt = Date.now();
    localStorage.setItem(storageKey, JSON.stringify(state));
  };

  const openDb = () =>
    new Promise((resolve, reject) => {
      if (!window.indexedDB) {
        reject(new Error("no-idb"));
        return;
      }

      const request = window.indexedDB.open(DB_NAME, 1);

      request.onupgradeneeded = () => {
        const db = request.result;

        if (!db.objectStoreNames.contains(STORE)) {
          db.createObjectStore(STORE);
        }
      };
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error || new Error("idb"));
    });

  const blobKey = (id) => assessmentId + ":" + userId + ":" + id;

  const putBlob = async (kind, blob, filename) => {
    const id = kind + "-" + Date.now().toString(36) + "-" + Math.random().toString(36).slice(2, 8);
    const db = await openDb();

    await new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, "readwrite");
      tx.objectStore(STORE).put(
        {
          blob,
          kind,
          filename: filename || "",
          type: blob.type || "",
        },
        blobKey(id)
      );
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });

    db.close();
    return id;
  };

  const getBlobRecord = async (id) => {
    const db = await openDb();
    const record = await new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, "readonly");
      const request = tx.objectStore(STORE).get(blobKey(id));
      request.onsuccess = () => resolve(request.result || null);
      request.onerror = () => reject(request.error);
    });
    db.close();
    return record;
  };

  const deleteBlob = async (id) => {
    try {
      const db = await openDb();
      await new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, "readwrite");
        tx.objectStore(STORE).delete(blobKey(id));
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
      });
      db.close();
    } catch (_error) {
      // ignore
    }
  };

  const objectUrlFor = async (id) => {
    if (blobUrls.has(id)) {
      return blobUrls.get(id);
    }

    const record = await getBlobRecord(id);

    if (!record?.blob) {
      return "";
    }

    const url = URL.createObjectURL(record.blob);
    blobUrls.set(id, url);
    return url;
  };

  const collectAssetIds = (value) => {
    const ids = [];
    const text = String(value || "");
    const re = /offline-asset:([a-z0-9-]+)/gi;
    let match;

    while ((match = re.exec(text))) {
      ids.push(match[1]);
    }

    return ids;
  };

  const serializeValue = (value) => {
    let text = String(value || "");

    blobUrls.forEach((url, id) => {
      if (url && text.includes(url)) {
        text = text.split(url).join(ASSET_PREFIX + id);
      }
    });

    return text.replace(/blob:[^\s"'<>]+/g, (blobUrl) => {
      let found = "";
      blobUrls.forEach((url, id) => {
        if (url === blobUrl) {
          found = ASSET_PREFIX + id;
        }
      });
      return found || blobUrl;
    });
  };

  const csrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const input = form.querySelector('input[name="_token"]');

    return input?.value || meta?.getAttribute("content") || "";
  };

  const setOffline = (next) => {
    const wasOffline = offline;
    offline = Boolean(next);
    form.toggleAttribute("data-pwa-skip-loading", offline);
    window.dispatchEvent(
      new CustomEvent("assessment:offline-state", {
        detail: { offline, pendingSubmit: readState().pendingSubmit, timedOut: readState().timedOut },
      })
    );

    if (wasOffline && !offline) {
      flush();
    }
  };

  const saveAnswer = (questionId, value) => {
    if (!questionId) {
      return;
    }

    const state = readState();
    const serialized = serializeValue(value);

    if (serialized === "") {
      delete state.answers[questionId];
    } else {
      state.answers[questionId] = serialized;
    }

    writeState(state);
  };

  const applyAnswerToItem = (item, value) => {
    const radios = item.querySelectorAll('input[type="radio"][name^="answers["]');

    if (radios.length > 0) {
      radios.forEach((radio) => {
        radio.checked = radio.value === value;
      });
      return;
    }

    const checks = item.querySelectorAll('input[type="checkbox"][name^="answers["]');

    if (checks.length > 0) {
      const selected = new Set(
        String(value)
          .split(",")
          .map((part) => part.trim())
          .filter(Boolean)
      );
      checks.forEach((box) => {
        box.checked = selected.has(box.value);
      });
      return;
    }

    const essay = item.querySelector(".session-quiz-essay-input");

    if (essay) {
      essay.value = value;
      return;
    }

    const named = item.querySelector(
      "[data-session-phat-am-answer-output], [data-session-listening-answer-output], [data-word-translate-complete-input], [data-fill-blanks-complete-input], [data-word-select-complete-input], [data-memory-complete-input], [data-word-link-complete-input], [data-word-order-complete-input], [data-category-sort-complete-input], [data-image-hotspot-complete-input], [data-image-label-drop-complete-input], [data-multi-select-complete-input], [data-listening-true-false-complete-input], [data-listening-multi-choice-complete-input], input[name^='answers['], textarea[name^='answers[']"
    );

    if (named) {
      named.value = value;
      named.dispatchEvent(new Event("input", { bubbles: true }));
      named.dispatchEvent(new Event("change", { bubbles: true }));
    }
  };

  const applyStoredAnswers = () => {
    const state = readState();

    Object.entries(state.answers).forEach(([questionId, value]) => {
      const item = form.querySelector('.session-quiz-item[data-question-id="' + questionId + '"]');

      if (item && String(value || "").trim() !== "") {
        applyAnswerToItem(item, String(value));
      }
    });
  };

  const lockForm = () => {
    form.querySelectorAll("input, button, textarea, select").forEach((el) => {
      if (el.name === "_token" || el.name === "_pwa") {
        return;
      }

      if (el.classList.contains("js-timed-out") || el.name === "timed_out") {
        return;
      }

      el.disabled = true;
    });
  };

  const csrfFormData = (body) => {
    const token = csrfToken();

    if (token) {
      body.append("_token", token);
    }

    return body;
  };

  const syncCsrf = (data) => {
    if (!data?.csrfHash) {
      return;
    }

    document.querySelectorAll('input[name="_token"]').forEach((input) => {
      input.value = data.csrfHash;
    });

    const meta = document.querySelector('meta[name="csrf-token"]');

    if (meta) {
      meta.setAttribute("content", data.csrfHash);
    }
  };

  const uploadImageRecord = async (record) => {
    if (window.ProgramPairImageEditor?.uploadImageBlobOnline) {
      return window.ProgramPairImageEditor.uploadImageBlobOnline(record.blob, record.filename || "image.png");
    }

    const url = form.dataset.imageUploadUrl || document.body.dataset.pairImageUploadUrl || "";

    if (!url) {
      throw new Error("no-image-upload");
    }

    const body = csrfFormData(new FormData());
    body.append("image", record.blob, record.filename || "image.png");
    const response = await fetch(url, { method: "POST", body, credentials: "same-origin" });
    const data = await response.json();
    syncCsrf(data);

    if (!response.ok || !data.ok) {
      throw new Error(data.message || "upload-image");
    }

    return { path: data.path || "", url: data.url || data.location || "" };
  };

  const uploadAudioRecord = async (record) => {
    const url = form.dataset.recordingUploadUrl || document.body.dataset.phatAmUploadUrl || "";

    if (!url) {
      throw new Error("no-audio-upload");
    }

    const body = csrfFormData(new FormData());
    const ext = (record.type || "").includes("ogg") ? "ogg" : "webm";
    body.append("recording", record.blob, record.filename || "recording." + ext);
    const response = await fetch(url, { method: "POST", body, credentials: "same-origin" });
    const data = await response.json();
    syncCsrf(data);

    if (!response.ok || !data.ok) {
      throw new Error(data.message || "upload-audio");
    }

    return data.path || data.url || "";
  };

  const replaceInState = (from, to) => {
    const state = readState();

    Object.keys(state.answers).forEach((questionId) => {
      state.answers[questionId] = String(state.answers[questionId] || "").split(from).join(to);
    });

    writeState(state);
    form.querySelectorAll("textarea, input").forEach((field) => {
      if (typeof field.value === "string" && field.value.includes(from)) {
        field.value = field.value.split(from).join(to);
      }
    });
  };

  const uploadPendingBlobs = async () => {
    const state = readState();
    const ids = new Set();

    Object.values(state.answers).forEach((value) => {
      collectAssetIds(value).forEach((id) => ids.add(id));
    });

    for (const id of ids) {
      const record = await getBlobRecord(id);

      if (!record?.blob) {
        continue;
      }

      const marker = ASSET_PREFIX + id;

      if (record.kind === "audio") {
        const path = await uploadAudioRecord(record);
        replaceInState(marker, path);
      } else {
        const result = await uploadImageRecord(record);
        const publicUrl = result.url || result.path;
        replaceInState(marker, publicUrl);
      }

      await deleteBlob(id);
    }
  };

  const nativeSubmit = () => {
    const state = readState();
    const timedOutInput = form.querySelector("[name='timed_out']");

    if (state.timedOut && timedOutInput) {
      timedOutInput.value = "1";
    }

    form.querySelectorAll("[disabled]").forEach((el) => {
      el.disabled = false;
    });

    if (typeof tinymce !== "undefined") {
      tinymce.triggerSave();
    }

    if (typeof window.assessmentPermitLeave === "function") {
      window.assessmentPermitLeave();
    }

    HTMLFormElement.prototype.submit.call(form);
  };

  const flushAnswers = async () => {
    if (typeof window.assessmentAutosaveAll === "function") {
      await window.assessmentAutosaveAll();
      return;
    }

    if (typeof window.quizAutosaveFlush === "function") {
      await Promise.resolve(window.quizAutosaveFlush());
    }
  };

  const flush = async () => {
    if (flushing || offline) {
      return;
    }

    flushing = true;

    try {
      await uploadPendingBlobs();
      applyStoredAnswers();
      await flushAnswers();

      if (offline) {
        return;
      }

      if (readState().pendingSubmit) {
        nativeSubmit();
      }
    } catch (_error) {
      setOffline(true);
      window.dispatchEvent(new Event("assessment:network-error"));
    } finally {
      flushing = false;
    }
  };

  const queueSubmit = (options = {}) => {
    const state = readState();
    state.pendingSubmit = true;
    state.timedOut = Boolean(options.timedOut) || state.timedOut;
    writeState(state);

    if (state.timedOut) {
      const timedOutInput = form.querySelector("[name='timed_out']");

      if (timedOutInput) {
        timedOutInput.value = "1";
      }

      lockForm();
    }

    window.dispatchEvent(
      new CustomEvent("assessment:offline-state", {
        detail: { offline, pendingSubmit: true, timedOut: state.timedOut },
      })
    );

    if (!offline) {
      flush();
    }
  };

  window.AssessmentOffline = {
    isOffline: () => offline,
    setOffline,
    saveAnswer,
    serializeValue,
    queueSubmit,
    flush,
    putBlob: async (kind, blob, filename) => {
      const id = await putBlob(kind, blob, filename);
      const url = URL.createObjectURL(blob);
      blobUrls.set(id, url);
      return { id, url, marker: ASSET_PREFIX + id };
    },
    resolveUrl: async (value) => {
      const text = String(value || "");

      if (text.startsWith(ASSET_PREFIX)) {
        return objectUrlFor(text.slice(ASSET_PREFIX.length));
      }

      return text;
    },
    getState: readState,
  };

  form.toggleAttribute("data-pwa-skip-loading", offline);
  applyStoredAnswers();

  const hydrateEssayAssets = async () => {
    const state = readState();

    for (const [questionId, value] of Object.entries(state.answers)) {
      const ids = collectAssetIds(value);

      if (ids.length === 0) {
        continue;
      }

      let html = String(value);

      for (const id of ids) {
        const url = await objectUrlFor(id);

        if (url) {
          html = html.split(ASSET_PREFIX + id).join(url);
        }
      }

      const item = form.querySelector('.session-quiz-item[data-question-id="' + questionId + '"]');
      const essay = item?.querySelector(".session-quiz-essay-input");

      if (essay) {
        essay.value = html;
        const editor = typeof tinymce !== "undefined" && essay.id ? tinymce.get(essay.id) : null;
        editor?.setContent(html);
      }
    }
  };

  hydrateEssayAssets();

  const bootState = readState();

  if (bootState.pendingSubmit && bootState.timedOut) {
    lockForm();
  }

  const onOfflineSubmit = (event) => {
    if (!offline) {
      return;
    }

    const timedOut = form.querySelector("[name='timed_out']")?.value === "1";

    if (!timedOut && event.eventPhase === Event.CAPTURING_PHASE) {
      return;
    }

    if (event.defaultPrevented) {
      return;
    }

    event.preventDefault();
    queueSubmit({ timedOut });
  };

  form.addEventListener("submit", onOfflineSubmit, true);
  form.addEventListener("submit", onOfflineSubmit);

  window.addEventListener("online", () => setOffline(false));
  window.addEventListener("assessment:network-ok", () => setOffline(false));
  window.addEventListener("assessment:network-error", () => setOffline(true));
  window.addEventListener("offline", () => setOffline(true));

  if (bootState.pendingSubmit && !offline) {
    window.setTimeout(() => flush(), 400);
  }
})();
