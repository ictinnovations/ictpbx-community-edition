-- ICTPBX AI Gateway — *99 voice entry point.
--
-- Deployed at /usr/ictcore/bin/freeswitch/ictpbx_ai_gateway.lua and reached ONLY by the
-- *99 feature-code dialplan (ictpbx_ai_99.xml). Fully additive: a fault here breaks *99
-- alone, never normal calls. Do NOT fold this into application.lua.
--
-- Auth model: the caller is already SIP-registered to their extension, so we trust the
-- calling extension as identity instead of prompting for a keypad PIN (softphone DTMF is
-- unreliable). We pass the extension to the gateway, which calls ICTCore /voice_identity to
-- mint a TENANT-scoped JWT for that extension's tenant. No DTMF is collected.
--
-- Flow: answer -> start mod_audio_stream capturing the caller's audio to the gateway
-- (ws://127.0.0.1:8790) with {uuid, extension} metadata -> the gateway authenticates by
-- extension, runs STT -> agent -> Piper TTS, and plays replies back into THIS leg via ESL
-- uuid_broadcast. We just hold the leg up until hangup.

local GW_WS   = "ws://127.0.0.1:8790"
local MIXTYPE = "mono"   -- caller audio only
local RATE    = "16k"    -- must match the gateway voice.sample_rate

session:answer()
session:sleep(500)

-- Identity = the caller's registered extension. sip_from_user is the registration username
-- (the extension); fall back to the caller-id number.
local extension = session:getVariable("sip_from_user")
if not extension or #extension == 0 then
  extension = session:getVariable("caller_id_number")
end
if not extension or #extension == 0 then
  session:hangup("NORMAL_CLEARING")
  return
end
extension = extension:gsub("[^0-9A-Za-z]", "")

local uuid = session:get_uuid()

-- Compact, space-free JSON. Sent as the first WS text frame.
local meta = string.format('{"uuid":"%s","extension":"%s"}', uuid, extension)

local api = freeswitch.API()
api:execute("uuid_audio_stream", uuid .. " start " .. GW_WS .. " " .. MIXTYPE .. " " .. RATE .. " " .. meta)

-- Hold the leg open so the gateway can inject TTS. Turn-taking/barge-in are gateway-side.
while session:ready() do
  session:sleep(1000)
end

api:execute("uuid_audio_stream", uuid .. " stop")
