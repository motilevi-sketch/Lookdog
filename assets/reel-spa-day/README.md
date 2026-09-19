# Spa Day Reel

Instagram Reel compiled from six 6-second AI-generated Shih Tzu grooming
clips, in two audio variants:

- `spa-day-reel.mp4` — voiceover over a low ambient pad
- `spa-day-reel-voice-only.mp4` — voiceover only, no music bed

Both share the same video master, captions and narration timing, so they are
interchangeable.

## Specs

| | |
|---|---|
| Resolution | 1080 x 1920 (9:16) |
| Duration | 34.2s |
| Video | H.264 High@4.0, 24fps |
| Audio | AAC 192k stereo, 48kHz |
| Loudness | -14.6 / -14.2 LUFS (Instagram target) |
| Size | 28 MB each, faststart enabled |

## Edit

Clips are sequenced as a grooming-day arc, joined with 0.35s crossfades.
Source clips were 416x752; upscaled with lanczos plus temporal denoise and
light sharpening. No watermark.

| # | Scene | Narration |
|---|---|---|
| 1 | Bath / shampoo | Spa day starts with a warm bath. Shampoo, suds, and zero complaints. |
| 2 | Towel dry | Towel time. Honestly, the best part of the whole routine. |
| 3 | Blow dry | Blow dry on low, lifting every layer until it floats. |
| 4 | Brushing | Then the brush. Slow strokes, top to tail, no tangles left behind. |
| 5 | Scissor trim | A little trim, a tiny bow, and that topknot is perfect. |
| 6 | Finished reveal | And there we go. Fluffed, fabulous, and fully aware of it. |

## Audio

Source clips had no usable audio (-46 dB), so the soundtrack is built from
scratch: synthesized voiceover, in the mixed version sitting over an ambient
pad held at -26 dB. The pad is deliberately quiet so a trending audio track can
be layered over it in-app without clashing.

The voice-only variant drops the pad entirely — gaps between lines are true
silence, which is the cleaner base if you plan to add a music track in
Instagram rather than ship it as-is.

Captions are burned in, matched to the narration and positioned clear of the
Instagram UI overlay so the reel reads correctly on mute.
