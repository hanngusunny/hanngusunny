<?php
  $quizQuestions = $quizQuestions ?? $questions ?? [];
  $richTextModel = $richTextModel ?? model(\App\Models\CourseMonthlyEvaluationModel::class);
  $answerLabels = $answerLabels ?? ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
  $showPoints = ! empty($showPoints);
  $answerModel = $answerModel ?? null;
  $savedAnswers = is_array($savedAnswers ?? null) ? $savedAnswers : [];
?>
              <?php foreach ($quizQuestions as $qIndex => $question): ?>
                <?php
                  $questionPairModel = model(\App\Models\StudyProgramSessionQuestionModel::class);
                  $question = $questionPairModel->normalizeQuestionRowForEdit(is_array($question) ? $question : []);
                  $questionId = (int) ($question['id'] ?? 0);
                  $questionType = (string) ($question['question_type'] ?? 'choice');
                  $questionType = $questionType === 'text' ? 'fill' : $questionType;
                  $questionType = $questionType === 'essay' ? 'fill' : $questionType;
                  $options = [];
                  $memoryPairs = $questionType === 'word_link'
                      ? $questionPairModel->decodePlayableWordLinkPairs($question)
                      : $questionPairModel->decodePlayableMemoryPairs($question);

                  if (! empty($question['options_json'])) {
                      $decoded = json_decode((string) $question['options_json'], true);

                      if (is_array($decoded) && ! isset($decoded['pairs'])) {
                          if (isset($decoded['options']) && is_array($decoded['options'])) {
                              $options = $decoded['options'];
                          } elseif (! isset($decoded['memory_pairs']) && ! isset($decoded['word_link_pairs']) && ! isset($decoded['word_order']) && ! isset($decoded['listening']) && ! isset($decoded['category_sort']) && ! isset($decoded['fill_blanks']) && ! isset($decoded['word_select']) && ! isset($decoded['word_translate']) && ! isset($decoded['phat_am']) && ! isset($decoded['image_hotspot']) && ! isset($decoded['image_label_drop'])) {
                              $options = $decoded;
                          }
                      }
                  }

                  $listeningConfig = $questionType === 'listening'
                      ? $questionPairModel->decodeListeningConfig($question)
                      : null;
                  $listeningBundle = $questionType === 'listening'
                      ? $questionPairModel->decodeListeningBundle($question)
                      : ['questions' => []];
                  $listeningQuestions = $listeningBundle['questions'] ?? [];
                  $listeningAnswerMode = is_array($listeningConfig)
                      ? (string) ($listeningConfig['answer_mode'] ?? 'choice')
                      : 'choice';
                  $listeningAudioUrl = is_array($listeningConfig)
                      ? $questionPairModel->resolveListeningAudioUrl((string) ($listeningConfig['audio'] ?? ''))
                      : '';
                  $listeningLegacyText = is_array($listeningConfig)
                      ? trim((string) ($listeningConfig['text'] ?? ''))
                      : '';
                  $listeningChoiceQuestion = is_array($listeningConfig)
                      ? trim((string) ($listeningConfig['choice_question'] ?? ''))
                      : '';
                  $listeningBoardMode = $listeningAudioUrl !== '' ? 'audio' : 'tts';
                  $showListeningBoard = $questionType === 'listening'
                      && is_array($listeningConfig)
                      && ($listeningAudioUrl !== '' || $listeningLegacyText !== '');
                  $phatAmBundle = $questionType === 'phat_am'
                      ? $questionPairModel->decodePhatAmBundle($question)
                      : ['prompt' => '', 'questions' => []];
                  $phatAmPrompt = trim((string) ($phatAmBundle['prompt'] ?? ''));
                  $phatAmQuestions = $phatAmBundle['questions'] ?? [];
                  $phatAmRequireRecording = $questionPairModel->normalizePhatAmRequireRecording($phatAmBundle['require_recording'] ?? true);
                  $phatAmAnswerMode = (string) (($phatAmQuestions[0] ?? [])['answer_mode'] ?? 'choice');
                  $savedRaw = trim((string) ($savedAnswers[$questionId] ?? ''));
                  $hasSavedDraft = $savedRaw !== '';
                ?>
                <fieldset class="session-quiz-item" data-question-id="<?= esc((string) $questionId) ?>" data-question-no="<?= esc((string) ((int) $qIndex + 1)) ?>"<?= $hasSavedDraft ? ' data-saved-draft="1"' : '' ?>>
                  <?= view('courses/_session_quiz_legend', [
                      'qIndex'        => $qIndex,
                      'question'        => $question,
                      'questionType'    => $questionType,
                      'richTextModel'   => $richTextModel,
                      'showPoints'      => $showPoints,
                      'answerModel'     => $answerModel,
                      'hasSavedDraft'   => $hasSavedDraft,
                  ]) ?>
                  <?php if ($questionType === 'fill'): ?>
                    <div class="session-quiz-essay">
                      <label class="visually-hidden" for="quiz-answer-<?= esc((string) $questionId) ?>">Câu trả lời của bạn</label>
                      <textarea
                        class="form-control session-quiz-essay-input"
                        id="quiz-answer-<?= esc((string) $questionId) ?>"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        rows="5"
                        placeholder="Viết câu trả lời của bạn..."
                      ><?= esc($savedRaw) ?></textarea>
                    </div>
                  <?php elseif ($questionType === 'memory_match'): ?>
                    <div
                      class="session-memory-board"
                      data-session-memory-board
                      data-memory-pairs="<?= esc(json_encode($memoryPairs, JSON_UNESCAPED_UNICODE)) ?>"
                    >
                      <div class="session-memory-grid" data-session-memory-grid></div>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-memory-complete-input
                      >
                      <p class="session-memory-status" data-session-memory-status></p>
                    </div>
                  <?php elseif ($questionType === 'word_link'): ?>
                    <div
                      class="session-word-link-board"
                      data-session-word-link-board
                      data-app-base="<?= esc(rtrim(base_url(), '/')) ?>"
                      data-word-link-pairs="<?= esc(json_encode($memoryPairs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                    >
                      <div class="session-word-link-columns">
                        <div class="session-word-link-column session-word-link-column--left">
                          <h4 class="session-word-link-column-title"><i class="bi bi-layout-sidebar-inset"></i> Bên trái</h4>
                          <div class="session-word-link-items" data-word-link-left></div>
                        </div>
                        <div class="session-word-link-column session-word-link-column--right">
                          <h4 class="session-word-link-column-title"><i class="bi bi-layout-sidebar-inset-reverse"></i> Bên phải</h4>
                          <div class="session-word-link-items" data-word-link-right></div>
                        </div>
                      </div>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-word-link-complete-input
                      >
                      <p class="session-word-link-status" data-word-link-status></p>
                    </div>
                  <?php elseif ($questionType === 'word_order'): ?>
                    <?php $wordOrderTokens = $questionPairModel->decodePlayableWordOrderTokens($question); ?>
                    <div
                      class="session-word-order-board"
                      data-session-word-order-board
                      data-word-order-tokens="<?= esc(json_encode($wordOrderTokens, JSON_UNESCAPED_UNICODE)) ?>"
                    >
                      <div class="session-word-order-head session-word-order-head--compact">
                        <div class="session-word-order-progress-wrap">
                          <span class="session-word-order-progress" data-word-order-progress>0/<?= esc((string) count($wordOrderTokens)) ?> từ</span>
                          <div class="session-word-order-progress-bar">
                            <span class="session-word-order-progress-fill" data-word-order-progress-fill></span>
                          </div>
                        </div>
                      </div>
                      <p class="session-word-order-zone-label"><i class="bi bi-chat-square-quote"></i> Câu của bạn</p>
                      <div class="session-word-order-sentence is-empty" data-word-order-sentence></div>
                      <p class="session-word-order-zone-label"><i class="bi bi-grid-3x3-gap"></i> Ngân hàng từ</p>
                      <div class="session-word-order-bank" data-word-order-bank></div>
                      <div class="session-word-order-live-wrap is-empty" data-word-order-live-wrap>
                        <p class="session-word-order-live-label"><i class="bi bi-text-paragraph"></i> Câu đang ghép</p>
                        <p class="session-word-order-live-text" data-word-order-live-text aria-live="polite"></p>
                      </div>
                      <div class="session-word-order-toolbar">
                        <button type="button" class="session-word-order-reset" data-word-order-reset>
                          <i class="bi bi-arrow-counterclockwise"></i> Xếp lại
                        </button>
                      </div>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-word-order-complete-input
                      >
                      <p class="session-word-order-status" data-word-order-status></p>
                    </div>
                  <?php elseif ($questionType === 'listening' && is_array($listeningConfig)): ?>
                    <?php if ($showListeningBoard): ?>
                    <div
                      class="session-listening-board"
                      data-session-listening-board
                      data-listening-mode="<?= esc($listeningBoardMode, 'attr') ?>"
                      data-listening-text="<?= esc($listeningLegacyText, 'attr') ?>"
                      data-listening-lang="<?= esc((string) ($listeningConfig['lang'] ?? 'ko-KR'), 'attr') ?>"
                      data-listening-audio-url="<?= esc($listeningAudioUrl, 'attr') ?>"
                    >
                      <div class="session-listening-player">
                        <div class="session-listening-audio-bar" data-listening-audio-bar>
                          <button type="button" class="session-listening-bar-play" data-listening-play aria-label="Phát / Tạm dừng">
                            <i class="bi bi-play-fill" aria-hidden="true"></i>
                          </button>
                          <div class="session-listening-bar-body">
                            <div class="session-listening-waveform" data-listening-waveform aria-hidden="true"></div>
                            <div class="session-listening-bar-progress">
                              <div class="session-listening-bar-track" data-listening-track>
                                <span class="session-listening-bar-fill" data-listening-progress-fill style="width: 0%"></span>
                              </div>
                              <input
                                type="range"
                                class="session-listening-bar-seek"
                                data-listening-seek
                                min="0"
                                max="100"
                                value="0"
                                step="0.01"
                                aria-label="Tiến độ phát"
                              >
                            </div>
                            <div class="session-listening-bar-meta">
                              <span class="session-listening-bar-time session-listening-bar-time--current" data-listening-current>0:00</span>
                              <span class="session-listening-bar-time-sep" aria-hidden="true">/</span>
                              <span class="session-listening-bar-time session-listening-bar-time--duration" data-listening-duration>0:00</span>
                            </div>
                          </div>
                          <button type="button" class="session-listening-bar-replay" data-listening-replay aria-label="Nghe lại">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                          </button>
                        </div>
                        <?php if ($listeningAudioUrl !== ''): ?>
                          <audio class="session-listening-audio-element" data-listening-audio preload="metadata" src="<?= esc($listeningAudioUrl, 'attr') ?>" hidden></audio>
                        <?php endif ?>
                        <p class="session-listening-status" data-listening-status>Bấm phát để nghe file MP3 đính kèm.</p>
                      </div>
                    </div>
                    <?php endif ?>
                    <?= view('courses/_session_listening_student_items', [
                        'question'           => $question,
                        'questionId'         => $questionId,
                        'questionPairModel'  => $questionPairModel,
                        'listeningQuestions' => $listeningQuestions,
                        'answerLabels'       => $answerLabels,
                        'savedRaw'           => $savedRaw,
                    ]) ?>
                  <?php elseif ($questionType === 'category_sort'): ?>
                    <?php $categorySortPlayable = $questionPairModel->decodePlayableCategorySort($question); ?>
                    <div
                      class="session-category-sort-board"
                      data-session-category-sort-board
                      data-category-sort-groups="<?= esc(json_encode($categorySortPlayable['groups'], JSON_UNESCAPED_UNICODE)) ?>"
                      data-category-sort-items="<?= esc(json_encode($categorySortPlayable['items'], JSON_UNESCAPED_UNICODE)) ?>"
                    >
                      <div class="session-category-sort-head session-category-sort-head--compact">
                        <div class="session-category-sort-progress-wrap">
                          <span class="session-category-sort-progress" data-category-sort-progress>0/<?= esc((string) count($categorySortPlayable['items'])) ?> thẻ</span>
                          <div class="session-category-sort-progress-bar">
                            <span class="session-category-sort-progress-fill" data-category-sort-progress-fill></span>
                          </div>
                        </div>
                      </div>
                      <div class="session-category-sort-bank-wrap">
                        <p class="session-category-sort-zone-label"><i class="bi bi-stack"></i> Thẻ chưa phân loại</p>
                        <div class="session-category-sort-bank is-empty" data-category-sort-bank></div>
                      </div>
                      <div class="session-category-sort-columns">
                        <?php foreach ($categorySortPlayable['groups'] as $groupIndex => $group): ?>
                          <div
                            class="session-category-sort-column session-category-sort-column--<?= esc((string) ((int) $groupIndex % 4)) ?>"
                            data-category-sort-column
                            data-category-sort-group-id="<?= esc((string) ($group['id'] ?? '')) ?>"
                          >
                            <h4 class="session-category-sort-column-title"><?= esc((string) ($group['label'] ?? 'Nhóm')) ?></h4>
                            <div class="session-category-sort-dropzone" data-category-sort-dropzone></div>
                          </div>
                        <?php endforeach ?>
                      </div>
                      <div class="session-category-sort-toolbar">
                        <button type="button" class="session-category-sort-reset" data-category-sort-reset>
                          <i class="bi bi-arrow-counterclockwise"></i> Làm lại
                        </button>
                      </div>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-category-sort-complete-input
                      >
                      <p class="session-category-sort-status" data-category-sort-status></p>
                    </div>
                  <?php elseif ($questionType === 'fill_blanks'): ?>
                    <?php
                      $fillBlanksData = $questionPairModel->decodeFillBlanksData($question);
                      $fillBlanksLineGroups = $questionPairModel->parseFillBlanksLineGroups((string) ($fillBlanksData['template'] ?? ''));
                    ?>
                    <div class="session-fill-blanks-board" data-session-fill-blanks-board>
                      <div class="session-fill-blanks-head">
                        <p class="session-fill-blanks-hint">Gõ từ vào từng ô — dùng <kbd>Tab</kbd> hoặc <kbd>Enter</kbd> để chuyển ô nhanh.</p>
                      </div>
                      <?= view('courses/_session_fill_blanks_student_sentence', [
                          'fillBlanksLineGroups' => $fillBlanksLineGroups,
                          'questionId'           => $questionId,
                          'inputIdPrefix'        => 'fill-blank',
                          'completeInputAttrs'   => 'name="answers[' . esc((string) $questionId) . ']" value="' . esc($savedRaw, 'attr') . '"',
                      ]) ?>
                      <p class="session-fill-blanks-status" data-fill-blanks-status></p>
                    </div>
                  <?php elseif ($questionType === 'word_select'): ?>
                    <?php
                      $wordSelectData = $questionPairModel->decodeWordSelectData($question);
                      $wordSelectLineGroups = $questionPairModel->parseFillBlanksLineGroups((string) ($wordSelectData['template'] ?? ''));
                      $wordSelectBank = $wordSelectData['word_bank'] ?? [];
                    ?>
                    <div class="session-word-select-board" data-session-word-select-board>
                      <div class="session-word-select-head">
                        <p class="session-word-select-hint">Bấm ô trống → chọn từ bên dưới, hoặc dùng menu ▾ trên từng ô.</p>
                      </div>
                      <?= view('courses/_session_word_select_student_sentence', [
                          'wordSelectLineGroups' => $wordSelectLineGroups,
                          'wordSelectBank'       => $wordSelectBank,
                          'questionId'           => $questionId,
                      ]) ?>
                      <div class="session-word-select-bank-wrap">
                        <p class="session-word-select-bank-label"><i class="bi bi-collection"></i> Danh sách từ</p>
                        <div class="session-word-select-bank">
                          <?php if ($wordSelectBank === []): ?>
                            <span class="muted">Chưa có từ trong danh sách</span>
                          <?php else: ?>
                            <?php foreach ($wordSelectBank as $bankWord): ?>
                              <button type="button" class="session-word-select-chip" data-word-select-chip data-word="<?= esc((string) $bankWord) ?>">
                                <?= esc((string) $bankWord) ?>
                              </button>
                            <?php endforeach ?>
                          <?php endif ?>
                        </div>
                      </div>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-word-select-complete-input
                      >
                      <p class="session-word-select-status" data-word-select-status></p>
                    </div>
                  <?php elseif ($questionType === 'word_translate'): ?>
                    <?php
                      $wordTranslateData = $questionPairModel->decodeWordTranslateData($question);
                      $wordTranslateItems = $wordTranslateData['items'] ?? [];
                      $wordTranslateColumns = (int) ($wordTranslateData['columns'] ?? 1);
                      $wordTranslateLayout = (string) ($wordTranslateData['layout'] ?? 'vertical');
                      $wordTranslateGridClass = 'session-word-translate-grid';
                      $wordTranslateGridClass .= $wordTranslateColumns === 2 ? ' session-word-translate-grid--cols-2' : '';
                      $wordTranslateGridClass .= $wordTranslateLayout === 'horizontal'
                          ? ' session-word-translate-grid--horizontal'
                          : ' session-word-translate-grid--vertical';
                    ?>
                    <div class="session-word-translate-board" data-session-word-translate-board>
                      <div class="session-word-translate-head">
                        <p class="session-word-translate-hint">Gõ bản dịch vào ô bên cạnh (hoặc bên dưới) từng mục — dùng <kbd>Tab</kbd> hoặc <kbd>Enter</kbd> để chuyển nhanh.</p>
                      </div>
                      <?php if ($wordTranslateItems === []): ?>
                        <p class="muted session-word-translate-empty">Chưa có từ nào trong câu hỏi.</p>
                      <?php else: ?>
                        <div class="<?= esc($wordTranslateGridClass) ?>">
                          <?php foreach ($wordTranslateItems as $itemIndex => $itemRow): ?>
                            <?php
                              $itemNo = (int) $itemIndex + 1;
                              $normalizedItem = $questionPairModel->normalizeWordTranslateItem(is_array($itemRow) ? $itemRow : []);
                              $sourceType = (string) ($normalizedItem['source_type'] ?? 'text');
                              $sourceText = trim((string) ($normalizedItem['source'] ?? ''));
                              $sourceImageUrl = $sourceType === 'image' && $sourceText !== ''
                                  ? $questionPairModel->resolveWordLinkMediaUrl($sourceText)
                                  : '';
                            ?>
                            <div class="session-word-translate-item session-word-translate-item--<?= esc($wordTranslateLayout) ?>">
                              <div class="session-word-translate-source-wrap">
                                <span class="session-word-translate-item-no"><?= esc((string) $itemNo) ?></span>
                                <?php if ($sourceType === 'image' && $sourceImageUrl !== ''): ?>
                                  <img
                                    class="session-word-translate-source-image"
                                    src="<?= esc($sourceImageUrl) ?>"
                                    alt="Từ gốc <?= esc((string) $itemNo) ?>"
                                  >
                                <?php else: ?>
                                  <span class="session-word-translate-source"><?= esc($sourceText !== '' ? $sourceText : '…') ?></span>
                                <?php endif ?>
                              </div>
                              <label class="visually-hidden" for="word-translate-<?= esc((string) $questionId) ?>-<?= esc((string) $itemIndex) ?>">Dịch từ <?= esc((string) $itemNo) ?></label>
                              <input
                                type="text"
                                class="session-word-translate-input"
                                id="word-translate-<?= esc((string) $questionId) ?>-<?= esc((string) $itemIndex) ?>"
                                data-word-translate-input
                                data-item-index="<?= esc((string) $itemIndex) ?>"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="Nhập bản dịch…"
                                aria-label="Dịch từ <?= esc((string) $itemNo) ?>"
                              >
                            </div>
                          <?php endforeach ?>
                        </div>
                      <?php endif ?>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-word-translate-complete-input
                      >
                      <p class="session-word-translate-status" data-word-translate-status></p>
                    </div>
                  <?php elseif ($questionType === 'phat_am'): ?>
                    <?= view('courses/_session_phat_am_student_items', [
                        'question'               => $question,
                        'questionId'             => $questionId,
                        'questionPairModel'      => $questionPairModel,
                        'phatAmPrompt'           => $phatAmPrompt,
                        'phatAmQuestions'        => $phatAmQuestions,
                        'phatAmRequireRecording' => $phatAmRequireRecording,
                        'answerLabels'           => $answerLabels,
                        'savedRaw'               => $savedRaw,
                    ]) ?>
                  <?php elseif ($questionType === 'image_hotspot'): ?>
                    <?php
                      $imageHotspotData = $questionPairModel->decodeImageHotspotData($question);
                      $imageHotspotUrl = $questionPairModel->resolveImageHotspotUrl((string) ($imageHotspotData['image'] ?? ''));
                      $imageHotspotTargetCount = count($imageHotspotData['targets'] ?? []);
                    ?>
                    <div
                      class="session-image-hotspot-board"
                      data-session-image-hotspot-board
                      data-target-count="<?= esc((string) $imageHotspotTargetCount) ?>"
                    >
                      <div class="session-image-hotspot-head">
                        <p class="session-image-hotspot-hint">Bấm lên ảnh để chọn <strong><?= esc((string) $imageHotspotTargetCount) ?></strong> điểm.</p>
                      </div>
                      <div class="session-image-hotspot-toolbar">
                        <button type="button" class="session-image-hotspot-reset" data-image-hotspot-reset>
                          <i class="bi bi-arrow-counterclockwise"></i> Chọn lại
                        </button>
                      </div>
                      <div class="session-image-hotspot-canvas-wrap">
                        <div class="session-image-hotspot-canvas" data-image-hotspot-canvas>
                          <img
                            class="session-image-hotspot-img"
                            data-image-hotspot-img
                            src="<?= esc($imageHotspotUrl) ?>"
                            alt="Ảnh câu hỏi"
                          >
                          <div class="session-image-hotspot-overlay" data-image-hotspot-overlay></div>
                        </div>
                      </div>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-image-hotspot-complete-input
                      >
                      <p class="session-image-hotspot-status" data-image-hotspot-status></p>
                    </div>
                  <?php elseif ($questionType === 'image_label_drop'): ?>
                    <?php
                      $imageLabelDropData = $questionPairModel->decodeImageLabelDropData($question);
                      $imageLabelDropUrl = $questionPairModel->resolveImageLabelDropUrl((string) ($imageLabelDropData['image'] ?? ''));
                      $studentLabelDropZones = array_map(static fn (array $zone): array => [
                          'id'    => (string) ($zone['id'] ?? ''),
                          'label' => (string) ($zone['label'] ?? ''),
                          'x'     => (float) ($zone['x'] ?? 0),
                          'y'     => (float) ($zone['y'] ?? 0),
                      ], $imageLabelDropData['zones'] ?? []);
                      $studentLabelDropLabels = array_map(static fn (array $label): array => [
                          'id'   => (string) ($label['id'] ?? ''),
                          'text' => (string) ($label['text'] ?? ''),
                      ], $imageLabelDropData['labels'] ?? []);
                    ?>
                    <div
                      class="session-image-label-drop-board"
                      data-session-image-label-drop-board
                      data-zone-radius="<?= esc((string) ($imageLabelDropData['zone_radius'] ?? 10)) ?>"
                      data-image-label-drop-zones="<?= esc(json_encode($studentLabelDropZones, JSON_UNESCAPED_UNICODE)) ?>"
                      data-image-label-drop-labels="<?= esc(json_encode($studentLabelDropLabels, JSON_UNESCAPED_UNICODE)) ?>"
                    >
                      <div class="session-image-label-drop-head">
                        <p class="session-image-label-drop-hint">Kéo từng nhãn từ danh sách bên dưới vào đúng vùng trên hình.</p>
                      </div>
                      <div class="session-image-label-drop-toolbar">
                        <button type="button" class="session-image-label-drop-reset" data-image-label-drop-reset>
                          <i class="bi bi-arrow-counterclockwise"></i> Làm lại
                        </button>
                      </div>
                      <div class="session-image-label-drop-canvas-wrap">
                        <div class="session-image-label-drop-canvas">
                          <img
                            class="session-image-label-drop-img"
                            src="<?= esc($imageLabelDropUrl) ?>"
                            alt="Ảnh câu hỏi kéo nhãn"
                          >
                          <div class="session-image-label-drop-overlay" data-image-label-drop-overlay></div>
                        </div>
                      </div>
                      <div class="session-image-label-drop-bank-wrap">
                        <span class="session-image-label-drop-bank-label">Nhãn cần kéo:</span>
                        <div class="session-image-label-drop-bank" data-image-label-drop-bank></div>
                      </div>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-image-label-drop-complete-input
                      >
                      <p class="session-image-label-drop-status" data-image-label-drop-status></p>
                    </div>
                  <?php elseif ($questionType === 'multi_select'): ?>
                    <?php $multiOptionCount = max(2, count($options)); ?>
                    <div class="session-quiz-options session-quiz-options--multi" data-session-multi-select-group>
                      <?php for ($i = 0; $i < $multiOptionCount; $i++): ?>
                        <?php
                          $key = chr(97 + $i);
                          $label = $answerLabels[$key] ?? strtoupper($key);
                          $optText = trim((string) ($options[$i] ?? ''));
                          if ($optText === '') {
                              continue;
                          }
                        ?>
                        <label class="session-quiz-option session-quiz-option--multi">
                          <input type="checkbox" value="<?= esc($key) ?>" data-multi-select-choice>
                          <span class="session-quiz-option-key"><?= esc($label) ?></span>
                          <span class="session-quiz-option-text course-rich-content"><?= app(\App\Support\Questions\QuestionBankItemModel::class)->renderOptionContent($optText, $label) ?></span>
                        </label>
                      <?php endfor ?>
                      <input
                        type="hidden"
                        name="answers[<?= esc((string) $questionId) ?>]"
                        value="<?= esc($savedRaw, 'attr') ?>"
                        data-multi-select-complete-input
                      >
                    </div>
                  <?php else: ?>
                  <div class="session-quiz-options">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                      <?php
                        $key = chr(97 + $i);
                        $label = $answerLabels[$key] ?? strtoupper($key);
                        $optText = trim((string) ($options[$i] ?? ''));
                        if ($optText === '') {
                            continue;
                        }
                      ?>
                      <label class="session-quiz-option">
                        <input type="radio" name="answers[<?= esc((string) $questionId) ?>]" value="<?= esc($key) ?>"<?= $savedRaw === $key ? ' checked' : '' ?>>
                        <span class="session-quiz-option-key"><?= esc($label) ?></span>
                        <span class="session-quiz-option-text course-rich-content"><?= app(\App\Support\Questions\QuestionBankItemModel::class)->renderOptionContent($optText, $label) ?></span>
                      </label>
                    <?php endfor ?>
                  </div>
                  <?php endif ?>
                </fieldset>
              <?php endforeach ?>
