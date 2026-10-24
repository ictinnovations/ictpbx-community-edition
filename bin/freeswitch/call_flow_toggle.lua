--[[
call_flow_toggle.lua <call_flow_uuid> <pin_required 0|1>

Reached from a call flow's feature code (dialplan written by CallFlow::sync_fs_dialplan).
Flips the flow between Open (Day) and Closed (Night) through the ICTCore API, which
saves the new status and rewrites the flow's dialplan, then tells the caller the result.
Uses the same [gatewayhub] credentials as application.lua.
]]

local uuid = argv[1] or ""
local pin_required = (argv[2] == "1")

if not session or not session:ready() then return end
if not uuid:match("^[%x%-]+$") then
  freeswitch.consoleLog("WARNING", "call_flow_toggle: bad call flow uuid\n")
  session:hangup("CALL_REJECTED")
  return
end

session:answer()
session:sleep(300)

local pin = ""
if pin_required then
  pin = session:playAndGetDigits(1, 10, 3, 5000, "#", "ivr/ivr-please_enter_pin_followed_by_pound.wav",
                                 "", "^\\d+$") or ""
  if pin == "" then
    session:hangup("NORMAL_CLEARING")
    return
  end
end

local CONF = (loadfile "/usr/ictcore/bin/freeswitch/lib/LIP.lua")()
local JSON = (loadfile "/usr/ictcore/bin/freeswitch/lib/JSON.lua")()
local aConf = CONF.load('/etc/ictcore.conf')
-- [gatewayhub] url is http://127.0.0.1/api/responses; the API root is its parent.
local api = tostring(aConf.gatewayhub.url):gsub("/responses/?$", "")

-- Values below are passed to curl as single-quoted shell words; JSON bodies are built
-- from the decoded INI values and digits only, with any single quote escaped.
local function sq(s) return "'" .. tostring(s):gsub("'", "'\\''") .. "'" end

local function http(method, path, body, token)
  local cmd = "curl -s -m 10 -X " .. method .. " -H 'Content-Type: application/json'"
  if token then cmd = cmd .. " -H " .. sq("Authorization: Bearer " .. token) end
  if body then cmd = cmd .. " -d " .. sq(JSON:encode(body)) end
  cmd = cmd .. " " .. sq(api .. path)
  local p = io.popen(cmd)
  local out = p and p:read("*a") or ""
  if p then p:close() end
  local ok, data = pcall(function() return JSON:decode(out) end)
  return ok and data or nil
end

local auth = http("POST", "/authenticate",
  { username = tostring(aConf.gatewayhub.username), password = tostring(aConf.gatewayhub.password) })
local token = auth and auth.token
if not token then
  freeswitch.consoleLog("ERR", "call_flow_toggle: ICTCore authentication failed\n")
  session:streamFile("ivr/ivr-disabled.wav")
  session:hangup("NORMAL_CLEARING")
  return
end

local res = http("POST", "/call_flows/" .. uuid .. "/toggle", pin_required and { pin = pin } or {}, token)
local status = res and res.call_flow_status
if status == "true" then
  freeswitch.consoleLog("INFO", "call_flow_toggle: " .. uuid .. " now OPEN\n")
  session:streamFile("ivr/ivr-enabled.wav")
elseif status == "false" then
  freeswitch.consoleLog("INFO", "call_flow_toggle: " .. uuid .. " now CLOSED\n")
  session:streamFile("ivr/ivr-disabled.wav")
else
  freeswitch.consoleLog("WARNING", "call_flow_toggle: toggle failed for " .. uuid .. "\n")
  session:streamFile("tone_stream://%(500,500,480,620);loops=3")
end
session:hangup("NORMAL_CLEARING")
