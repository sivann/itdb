#!/bin/bash

# Use a Here Document to pass the AppleScript cleanly to osascript
osascript <<'EOF'
  tell application "iTerm2"
    
    set target_name to "✳ Item Editing (claude)"
    
    # Iterate through all windows
    repeat with aWindow in windows
      
      # Iterate through all tabs in the window
      repeat with aTab in tabs of aWindow
        
        # Iterate through all sessions in the tab
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
              
              # 2. Press the physical RETURN key (key code 36)
              key code 36
            end tell
            # --- END: Simulate Typing and Enter via System Events ---
            
            return "Command 'resume' sent via physical keystroke simulation."
          end if
          
        end repeat
      end repeat
    end repeat
    
    # If the loops finish without finding the session
    return "Error: Target session not found."
  end tell
EOF
