Option Explicit

Dim shell, fso, folder, pythonw, agent

Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
folder = fso.GetParentFolderName(WScript.ScriptFullName)
pythonw = folder & "\runtime\python\pythonw.exe"
agent = folder & "\agent_windows.py"

shell.CurrentDirectory = folder

Do
    If fso.FileExists(pythonw) And fso.FileExists(agent) Then
        shell.Run """" & pythonw & """ """ & agent & """", 0, True
    End If

    WScript.Sleep 10000
Loop
