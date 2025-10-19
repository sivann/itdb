#!/bin/bash

# Usage: ./resume_claude.sh HH:MM
TARGET_TIME="$1"

if [ -z "$TARGET_TIME" ]; then
  echo "Usage: $0 HH:MM (24-hour format)"
  exit 1
fi

# Convert to seconds since midnight
target_sec=$(( $(date -j -f "%H:%M" "$TARGET_TIME" +%s) ))
now_sec=$(( $(date +%s) ))

# If target time is in the future today, wait; else, exit
if [ "$target_sec" -le "$now_sec" ]; then
  echo "⏱️ $TARGET_TIME is in the past. Exiting."
  exit 1
fi

# Wait until the time comes
echo "🕒 Waiting until $TARGET_TIME..."
sleep $(( target_sec - now_sec ))

# === After waiting, run Claude automation ===

DIR=$(pwd)

osascript <<EOF
tell application "Terminal"
    do script "cd \"$DIR\"; claude --resume"
    activate
end tell
EOF

sleep 2

osascript <<EOF
tell application "System Events"
    tell application "Terminal" to activate
    delay 0.5

    keystroke "1"
    delay 0.5

    keystroke "continue"
    delay 0.5
    key code 36 using command down
end tell
EOF
