#!/bin/bash

# Use a Here Document to pass the AppleScript cleanly to osascript
osascript <<'EOF'
  tell application "iTerm2"
    
    set target_name to "✳ Item Editing (claude)"
    
    # Iterate through all windows, tabs, and sessions to find the target
    repeat with aWindow in windows
      repeat with aTab in tabs of aWindow
        repeat with aSession in sessions of aTab
          
          # Check if the current session name matches the target name
          if name of aSession is equal to target_name then
            
            # Target found! Activate the containing window/tab and select the session.
            select aSession
            activate
            
            # --- START: Simulate Typing and Enter via System Events ---
            tell application "System Events"
              # 1. Type the command "resume"
              keystroke "resume"
              
              # 2. Press the RETURN key (Enter)
              keystroke (ASCII character 13)
            end tell
            # --- END: Simulate Typing and Enter via System Events ---
            
            return "Command 'resume' sent via keystroke simulation."
          end if
          
        end repeat
      end repeat
    end repeat
    
    # If the loops finish without finding the session
    return "Error: Target session not found."
  end tell
EOF
